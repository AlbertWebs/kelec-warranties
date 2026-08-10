<?php

namespace App\Services\Odoo;

use App\Models\Product;
use App\Services\SettingsService;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OdooProductService
{
    public function __construct(
        protected OdooClient $client,
        protected SettingsService $settingsService,
    ) {}

    /**
     * @return array{found: bool, product?: array<string, mixed>, sale?: array<string, mixed>|null, customer?: array<string, mixed>|null, message: string}
     */
    public function lookupBySerial(string $serialNumber): array
    {
        $serialNumber = trim($serialNumber);
        $result = $this->client->validateSerial($serialNumber);

        if ($result['found'] ?? false) {
            return $result;
        }

        // Product lookup can match barcode/SKU/name even when no stock.lot/POS pack lot exists yet.
        foreach ($this->queryCandidates($serialNumber) as $candidate) {
            $odooProduct = $this->searchProduct($candidate, allowFuzzyName: false);
            if (! $odooProduct) {
                continue;
            }

            $product = $this->upsertProductFromOdoo($odooProduct);

            return [
                'found' => true,
                'message' => 'Product matched in Odoo catalog.',
                'product' => [
                    'id' => $product->id,
                    'odoo_product_id' => $product->odoo_product_id ?: $product->odoo_id,
                    'odoo_serial_id' => null,
                    'name' => $product->customerFacingName(),
                    'model' => $product->model ?: $product->default_code ?: $product->sku ?: $product->customerFacingName(),
                    'category_id' => $product->product_category_id,
                ],
                'sale' => [
                    'purchase_date' => null,
                    'invoice_number' => null,
                    'branch_name' => null,
                    'odoo_pos_order_id' => null,
                ],
                'customer' => null,
            ];
        }

        return $result;
    }

    /**
     * @param  array<int, mixed>  $domain
     * @return array<int, array<string, mixed>>
     */
    public function fetchProductsBatch(int $offset = 0, int $limit = 100, array $domain = []): array
    {
        $fields = [
            'id',
            'product_tmpl_id',
            'name',
            'display_name',
            'default_code',
            'barcode',
            'type',
            'categ_id',
            'description',
            'description_sale',
            'list_price',
            'standard_price',
            'currency_id',
            'uom_id',
            'active',
            'sale_ok',
            'purchase_ok',
            'tracking',
            'create_date',
            'write_date',
            'image_1920',
        ];

        $result = $this->executeKw('product.product', 'search_read', [
            $domain,
            $fields,
            $offset,
            $limit,
            'id asc',
        ]);

        return is_array($result) ? $result : [];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function fetchSingleProduct(int|string $odooId): ?array
    {
        $result = $this->executeKw('product.product', 'search_read', [
            [['id', '=', (int) $odooId]],
            ['id', 'product_tmpl_id', 'name', 'display_name', 'default_code', 'barcode', 'type', 'categ_id', 'description', 'description_sale', 'list_price', 'standard_price', 'currency_id', 'uom_id', 'active', 'sale_ok', 'purchase_ok', 'tracking', 'create_date', 'write_date', 'image_1920'],
            0,
            1,
            'id asc',
        ]);

        return is_array($result) && isset($result[0]) ? $result[0] : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function searchProduct(string $query, bool $allowFuzzyName = true): ?array
    {
        $query = trim($query);
        if ($query === '') {
            return null;
        }

        $exactFields = ['barcode', 'default_code', 'name'];
        foreach ($this->queryCandidates($query) as $candidate) {
            foreach ($exactFields as $field) {
                $result = $this->executeKw('product.product', 'search_read', [
                    [[$field, '=', $candidate]],
                ], [
                    'fields' => ['id', 'product_tmpl_id', 'name', 'display_name', 'default_code', 'barcode', 'type', 'categ_id', 'description', 'description_sale', 'list_price', 'standard_price', 'currency_id', 'uom_id', 'active', 'sale_ok', 'purchase_ok', 'tracking', 'create_date', 'write_date', 'image_1920'],
                    'limit' => 1,
                    'order' => 'id asc',
                ]);

                if (is_array($result) && isset($result[0])) {
                    return $result[0];
                }
            }
        }

        if ($allowFuzzyName) {
            $nameSearch = $this->executeKw('product.product', 'search_read', [
                [['name', 'ilike', $query]],
            ], [
                'fields' => ['id', 'product_tmpl_id', 'name', 'display_name', 'default_code', 'barcode', 'type', 'categ_id', 'description', 'description_sale', 'list_price', 'standard_price', 'currency_id', 'uom_id', 'active', 'sale_ok', 'purchase_ok', 'tracking', 'create_date', 'write_date', 'image_1920'],
                'limit' => 1,
                'order' => 'id asc',
            ]);

            if (is_array($nameSearch) && isset($nameSearch[0])) {
                return $nameSearch[0];
            }
        }

        $serialLot = $this->findProductBySerialLot($query);
        if ($serialLot) {
            return $serialLot;
        }

        return null;
    }

    /**
     * @return list<string>
     */
    protected function queryCandidates(string $query): array
    {
        $trimmed = trim($query);

        return array_values(array_unique(array_filter([
            $trimmed,
            strtoupper($trimmed),
            strtolower($trimmed),
        ], fn (string $value) => $value !== '')));
    }

    /**
     * @param  array<string, mixed>  $odooProduct
     * @return array<string, mixed>
     */
    public function mapProductData(array $odooProduct): array
    {
        $odooId = (string) ($odooProduct['id'] ?? '');
        $template = $this->relationIdAndName($odooProduct['product_tmpl_id'] ?? null);
        $category = $this->relationIdAndName($odooProduct['categ_id'] ?? null);
        $currency = $this->relationIdAndName($odooProduct['currency_id'] ?? null);
        $uom = $this->relationIdAndName($odooProduct['uom_id'] ?? null);
        $name = (string) ($odooProduct['name'] ?? 'Unnamed product');
        $displayName = (string) ($odooProduct['display_name'] ?? $name);
        $defaultCode = $this->nullIfEmpty($odooProduct['default_code'] ?? null);
        $barcode = $this->nullIfEmpty($odooProduct['barcode'] ?? null);

        return [
            'odoo_id' => $odooId !== '' ? $odooId : null,
            'odoo_product_id' => $odooId !== '' ? $odooId : null,
            'product_template_id' => $template['id'],
            'name' => $name,
            'display_name' => $displayName,
            'default_code' => $defaultCode,
            'product_code' => $defaultCode,
            'sku' => $defaultCode,
            'barcode' => $barcode,
            'serial_number' => $barcode,
            'model' => $defaultCode,
            'product_type' => $this->nullIfEmpty($odooProduct['type'] ?? null),
            'category_id' => $category['id'],
            'category_name' => $category['name'],
            'brand_name' => 'K-Elec',
            'description' => $this->nullIfEmpty($odooProduct['description'] ?? null),
            'description_sale' => $this->nullIfEmpty($odooProduct['description_sale'] ?? null),
            'list_price' => isset($odooProduct['list_price']) ? (float) $odooProduct['list_price'] : null,
            'standard_price' => isset($odooProduct['standard_price']) ? (float) $odooProduct['standard_price'] : null,
            'currency' => $currency['name'],
            'uom_id' => $uom['id'],
            'uom_name' => $uom['name'],
            'active' => (bool) ($odooProduct['active'] ?? true),
            'is_active' => (bool) ($odooProduct['active'] ?? true),
            'sale_ok' => (bool) ($odooProduct['sale_ok'] ?? true),
            'purchase_ok' => (bool) ($odooProduct['purchase_ok'] ?? false),
            'tracking' => $this->nullIfEmpty($odooProduct['tracking'] ?? null),
            'serial_tracking_enabled' => in_array(($odooProduct['tracking'] ?? ''), ['serial', 'lot'], true),
            'image_url' => isset($odooProduct['image_1920']) && is_string($odooProduct['image_1920']) && $odooProduct['image_1920'] !== ''
                ? 'data:image/png;base64,'.$odooProduct['image_1920']
                : null,
            'odoo_created_at' => $this->asTimestamp($odooProduct['create_date'] ?? null),
            'odoo_updated_at' => $this->asTimestamp($odooProduct['write_date'] ?? null),
            'last_synced_at' => now(),
            'sync_status' => 'synced',
            'is_odoo_managed' => true,
            'raw_odoo_data' => $odooProduct,
        ];
    }

    /**
     * @param  array<string, mixed>  $odooProduct
     */
    public function upsertProductFromOdoo(array $odooProduct): Product
    {
        $mapped = $this->mapProductData($odooProduct);
        $odooId = $mapped['odoo_id'] ?? null;

        if (! $odooId) {
            throw new RuntimeException('Cannot upsert product without Odoo id.');
        }

        $existing = Product::query()->where('odoo_id', $odooId)->first();
        if (! $existing && ! empty($mapped['default_code'])) {
            $existing = Product::query()->where('default_code', $mapped['default_code'])->first();
        }
        if (! $existing && ! empty($mapped['barcode'])) {
            $existing = Product::query()->where('barcode', $mapped['barcode'])->first();
        }

        if ($existing) {
            $existing->fill($mapped)->save();

            return $existing->fresh();
        }

        return Product::create($mapped);
    }

    /**
     * @param  array<int, mixed>  $args
     * @param  array<string, mixed>  $kwargs
     * @return array<int, mixed>|array<string, mixed>|null
     */
    protected function executeKw(string $model, string $method, array $args, array $kwargs = []): array|null
    {
        $uid = $this->authenticate();
        $config = $this->config();

        $rpcArgs = [
            $config['database'],
            $uid,
            $config['password'],
            $model,
            $method,
            $args,
        ];

        if ($kwargs !== []) {
            $rpcArgs[] = $kwargs;
        }

        $payload = [
            'jsonrpc' => '2.0',
            'method' => 'call',
            'params' => [
                'service' => 'object',
                'method' => 'execute_kw',
                'args' => $rpcArgs,
            ],
            'id' => time(),
        ];

        $response = $this->http()->post($config['base_url'].'/jsonrpc', $payload);
        if (! $response->successful()) {
            throw new RuntimeException('Odoo API request failed with HTTP '.$response->status().'.');
        }

        if ($response->json('error')) {
            $message = $response->json('error.data.message')
                ?? $response->json('error.message')
                ?? 'Unknown Odoo error';
            throw new RuntimeException('Odoo API error: '.$message);
        }

        return $response->json('result');
    }

    protected function authenticate(): int
    {
        $config = $this->config();
        $cacheKey = 'odoo_product_uid_'.md5($config['base_url'].'|'.$config['database'].'|'.$config['username']);

        return (int) Cache::remember($cacheKey, now()->addMinutes(20), function () use ($config) {
            $payload = [
                'jsonrpc' => '2.0',
                'method' => 'call',
                'params' => [
                    'service' => 'common',
                    'method' => 'authenticate',
                    'args' => [
                        $config['database'],
                        $config['username'],
                        $config['password'],
                        new \stdClass,
                    ],
                ],
                'id' => time(),
            ];

            $response = $this->http()->post($config['base_url'].'/jsonrpc', $payload);
            if (! $response->successful()) {
                throw new RuntimeException('Odoo authentication failed with HTTP '.$response->status().'.');
            }

            if ($response->json('error')) {
                $message = $response->json('error.message') ?? $response->json('error.data.message') ?? 'Unknown auth error';
                throw new RuntimeException('Odoo authentication error: '.$message);
            }

            $uid = (int) ($response->json('result') ?? 0);
            if ($uid <= 0) {
                throw new RuntimeException('Odoo authentication failed. Invalid credentials.');
            }

            return $uid;
        });
    }

    /**
     * @return array{base_url: string, database: string, username: string, password: string, timeout: int}
     */
    protected function config(): array
    {
        $baseUrl = rtrim((string) ($this->settingsService->get('odoo_base_url') ?: env('ODOO_URL') ?: env('ODOO_BASE_URL')), '/');
        $database = (string) ($this->settingsService->get('odoo_database') ?: env('ODOO_DATABASE'));
        $username = (string) ($this->settingsService->get('odoo_username') ?: env('ODOO_USERNAME'));
        $password = (string) ($this->settingsService->get('odoo_api_key') ?: env('ODOO_API_KEY') ?: env('ODOO_PASSWORD'));
        $timeout = (int) ($this->settingsService->get('odoo_timeout') ?: env('ODOO_TIMEOUT', 15));

        if ($baseUrl === '' || $database === '' || $username === '' || $password === '') {
            throw new RuntimeException('Odoo product sync is not configured.');
        }

        return [
            'base_url' => $baseUrl,
            'database' => $database,
            'username' => $username,
            'password' => $password,
            'timeout' => max(5, $timeout),
        ];
    }

    protected function http(): PendingRequest
    {
        return Http::acceptJson()
            ->timeout($this->config()['timeout'])
            ->retry(2, 300);
    }

    /**
     * @param  mixed  $value
     * @return array{id: string|null, name: string|null}
     */
    protected function relationIdAndName(mixed $value): array
    {
        if (is_array($value)) {
            return [
                'id' => isset($value[0]) ? (string) $value[0] : null,
                'name' => isset($value[1]) ? (string) $value[1] : null,
            ];
        }

        return ['id' => null, 'name' => null];
    }

    protected function nullIfEmpty(mixed $value): ?string
    {
        $string = is_string($value) ? trim($value) : (is_scalar($value) ? trim((string) $value) : '');

        return $string === '' ? null : $string;
    }

    protected function asTimestamp(mixed $value): ?Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function findProductBySerialLot(string $serial): ?array
    {
        $serial = trim($serial);
        if ($serial === '') {
            return null;
        }

        $candidates = array_values(array_unique(array_filter([
            $serial,
            strtoupper($serial),
            strtolower($serial),
        ])));

        foreach (['stock.lot', 'stock.production.lot'] as $lotModel) {
            foreach ($candidates as $candidate) {
                try {
                    $lots = $this->executeKw($lotModel, 'search_read', [
                        [['name', '=', $candidate]],
                    ], [
                        'fields' => ['id', 'name', 'product_id'],
                        'limit' => 1,
                        'order' => 'id desc',
                    ]);
                } catch (\Throwable) {
                    continue;
                }

                if (! is_array($lots) || ! isset($lots[0])) {
                    continue;
                }

                $productId = is_array($lots[0]['product_id'] ?? null) ? (int) ($lots[0]['product_id'][0] ?? 0) : 0;
                if ($productId <= 0) {
                    continue;
                }

                return $this->fetchSingleProduct($productId);
            }
        }

        foreach ($candidates as $candidate) {
            try {
                $rows = $this->executeKw('pos.pack.operation.lot', 'search_read', [
                    [['lot_name', '=', $candidate]],
                ], [
                    'fields' => ['id', 'lot_name', 'product_id'],
                    'limit' => 1,
                    'order' => 'id desc',
                ]);
            } catch (\Throwable) {
                continue;
            }

            if (! is_array($rows) || ! isset($rows[0])) {
                continue;
            }

            $productId = is_array($rows[0]['product_id'] ?? null) ? (int) ($rows[0]['product_id'][0] ?? 0) : 0;
            if ($productId <= 0) {
                continue;
            }

            return $this->fetchSingleProduct($productId);
        }

        return null;
    }
}
