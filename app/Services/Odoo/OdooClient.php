<?php

namespace App\Services\Odoo;

use App\Models\OdooSyncLog;
use App\Models\Product;
use App\Services\SettingsService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class OdooClient
{
    public function enabled(): bool
    {
        $settings = app(SettingsService::class);
        $value = $settings->get('odoo_enabled');

        if ($value === null) {
            return filter_var(env('ODOO_ENABLED', false), FILTER_VALIDATE_BOOLEAN);
        }

        return (bool) $value;
    }

    public function mockMode(): bool
    {
        $settings = app(SettingsService::class);
        $value = $settings->get('odoo_mock_mode');

        if ($value === null) {
            return filter_var(env('ODOO_MOCK_MODE', true), FILTER_VALIDATE_BOOLEAN);
        }

        return (bool) $value;
    }

    /**
     * @return array{ok: bool, message: string, flash: string, uid?: int}
     */
    public function testConnection(): array
    {
        $settings = app(SettingsService::class);
        $baseUrl = trim((string) $settings->get('odoo_base_url', ''));
        $database = trim((string) $settings->get('odoo_database', ''));
        $username = trim((string) $settings->get('odoo_username', ''));
        $apiKey = (string) $settings->get('odoo_api_key', '');

        $missing = [];
        if ($baseUrl === '') {
            $missing[] = 'Base URL';
        }
        if ($database === '') {
            $missing[] = 'database';
        }
        if ($username === '') {
            $missing[] = 'username';
        }
        if ($apiKey === '') {
            $missing[] = 'API key';
        }

        if ($missing !== []) {
            $this->log('authenticate', $baseUrl !== '' ? $baseUrl.'/jsonrpc' : null, null, 'Incomplete Odoo credentials', 'failed');

            $list = implode(', ', $missing);

            return [
                'ok' => false,
                'flash' => 'error',
                'message' => $this->mockMode()
                    ? "Mock mode is on and Odoo credentials are incomplete. Missing: {$list}. Add them under Admin → Settings → Odoo, then test again."
                    : "Odoo connection failed. Missing: {$list}.",
            ];
        }

        try {
            $uid = $this->authenticate();

            if ($uid <= 0) {
                throw new \RuntimeException('Odoo authentication returned an invalid user id.');
            }

            $this->log('authenticate', $this->baseUrl().'/jsonrpc', 200, null, 'success');

            $message = 'Connected to Odoo successfully.';
            if ($this->mockMode()) {
                $message .= ' Note: mock mode is still enabled for day-to-day syncs.';
            }
            if (! $this->enabled()) {
                $message .= ' Note: Odoo integration is currently disabled.';
            }

            return ['ok' => true, 'flash' => 'success', 'message' => $message, 'uid' => $uid];
        } catch (Throwable $e) {
            $this->log('authenticate', $this->baseUrl().'/jsonrpc', 500, $e->getMessage(), 'failed');

            return [
                'ok' => false,
                'flash' => 'error',
                'message' => 'Unable to connect to Odoo: '.$e->getMessage(),
            ];
        }
    }

    /**
     * @return array{found: bool, product?: array<string, mixed>, sale?: array<string, mixed>|null, customer?: array<string, mixed>|null, message: string}
     */
    public function validateSerial(string $serialNumber): array
    {
        if ($this->mockMode()) {
            return $this->mockSerialLookup($serialNumber);
        }

        if (! $this->enabled()) {
            throw new \RuntimeException('Odoo integration is disabled.');
        }

        $serialNumber = trim($serialNumber);
        if ($serialNumber === '') {
            return ['found' => false, 'message' => 'Serial number not found in Odoo.'];
        }

        try {
            $uid = $this->authenticate();
            $match = $this->findSerialMatch($uid, $serialNumber);

            if ($match === null) {
                $this->log('validate_serial', 'stock.lot|pos.pack.operation.lot', 404, 'Serial not found', 'not_found', $serialNumber);

                return ['found' => false, 'message' => 'Serial number not found in Odoo.'];
            }

            $sale = $match['sale'] ?? $this->findSaleForLot($uid, (int) ($match['lot_id'] ?? 0), $serialNumber);
            if ($sale === null) {
                $sale = $this->findSaleBySerialName($uid, $serialNumber);
            }

            $customer = is_array($sale) ? ($sale['customer'] ?? null) : null;
            if (is_array($sale)) {
                unset($sale['customer']);
            }

            $this->log('validate_serial', $match['source'], 200, null, 'success', $serialNumber);

            return [
                'found' => true,
                'message' => 'Serial number validated against Odoo.',
                'product' => [
                    'odoo_product_id' => $match['product_id'] ?? null,
                    'odoo_serial_id' => $match['lot_id'] ?? null,
                    'name' => $match['product_name'] ?? null,
                    'model' => $match['model'] ?? null,
                ],
                'sale' => $sale,
                'customer' => $customer,
            ];
        } catch (Throwable $e) {
            $this->log('validate_serial', 'stock.lot', 500, $e->getMessage(), 'failed', $serialNumber);
            throw $e;
        }
    }

    /**
     * @return array{source: string, lot_id: int|null, product_id: int|null, product_name: string|null, model: string|null, sale?: array<string, mixed>|null}|null
     */
    protected function findSerialMatch(int $uid, string $serialNumber): ?array
    {
        foreach ($this->serialCandidates($serialNumber) as $candidate) {
            foreach (['stock.lot', 'stock.production.lot'] as $lotModel) {
                try {
                    $lots = $this->executeKw($uid, $lotModel, 'search_read', [
                        [['name', '=', $candidate]],
                    ], [
                        'fields' => ['id', 'name', 'product_id', 'ref'],
                        'limit' => 1,
                        'order' => 'id desc',
                    ]);
                } catch (Throwable) {
                    $lots = [];
                }

                if ($lots !== []) {
                    $lot = $lots[0];

                    return [
                        'source' => $lotModel,
                        'lot_id' => isset($lot['id']) ? (int) $lot['id'] : null,
                        'product_id' => is_array($lot['product_id'] ?? null) ? (int) $lot['product_id'][0] : null,
                        'product_name' => is_array($lot['product_id'] ?? null) ? (string) $lot['product_id'][1] : null,
                        'model' => $this->nullIfEmpty($lot['ref'] ?? null),
                    ];
                }
            }
        }

        // Case-insensitive lot fallback.
        try {
            $lots = $this->executeKw($uid, 'stock.lot', 'search_read', [
                [['name', 'ilike', $serialNumber]],
            ], [
                'fields' => ['id', 'name', 'product_id', 'ref'],
                'limit' => 5,
                'order' => 'id desc',
            ]);

            foreach ($lots as $lot) {
                if (strcasecmp((string) ($lot['name'] ?? ''), $serialNumber) === 0) {
                    return [
                        'source' => 'stock.lot',
                        'lot_id' => isset($lot['id']) ? (int) $lot['id'] : null,
                        'product_id' => is_array($lot['product_id'] ?? null) ? (int) $lot['product_id'][0] : null,
                        'product_name' => is_array($lot['product_id'] ?? null) ? (string) $lot['product_id'][1] : null,
                        'model' => $this->nullIfEmpty($lot['ref'] ?? null),
                    ];
                }
            }
        } catch (Throwable) {
            // Continue to POS lookup.
        }

        return $this->findPosPackLotMatch($uid, $serialNumber);
    }

    /**
     * POS serials are often stored on pos.pack.operation.lot before/without a stock.lot row.
     *
     * @return array{source: string, lot_id: int|null, product_id: int|null, product_name: string|null, model: string|null, sale?: array<string, mixed>|null}|null
     */
    protected function findPosPackLotMatch(int $uid, string $serialNumber): ?array
    {
        $rows = [];

        foreach ($this->serialCandidates($serialNumber) as $candidate) {
            try {
                $rows = $this->executeKw($uid, 'pos.pack.operation.lot', 'search_read', [
                    [['lot_name', '=', $candidate]],
                ], [
                    'fields' => ['id', 'lot_name', 'product_id', 'pos_order_line_id', 'order_id', 'create_date'],
                    'limit' => 1,
                    'order' => 'id desc',
                ]);
            } catch (Throwable) {
                $rows = [];
            }

            if ($rows !== []) {
                break;
            }
        }

        if ($rows === []) {
            try {
                $candidates = $this->executeKw($uid, 'pos.pack.operation.lot', 'search_read', [
                    [['lot_name', 'ilike', $serialNumber]],
                ], [
                    'fields' => ['id', 'lot_name', 'product_id', 'pos_order_line_id', 'order_id', 'create_date'],
                    'limit' => 5,
                    'order' => 'id desc',
                ]);

                foreach ($candidates as $row) {
                    if (strcasecmp((string) ($row['lot_name'] ?? ''), $serialNumber) === 0) {
                        $rows = [$row];
                        break;
                    }
                }
            } catch (Throwable) {
                $rows = [];
            }
        }

        if ($rows === []) {
            return null;
        }

        $row = $rows[0];
        $orderId = is_array($row['order_id'] ?? null) ? (int) $row['order_id'][0] : 0;
        $sale = $orderId > 0 ? $this->saleFromPosOrder($uid, $orderId) : [
            'purchase_date' => isset($row['create_date']) ? substr((string) $row['create_date'], 0, 10) : null,
            'invoice_number' => null,
            'branch_name' => null,
            'odoo_pos_order_id' => null,
            'customer' => null,
        ];

        return [
            'source' => 'pos.pack.operation.lot',
            'lot_id' => isset($row['id']) ? (int) $row['id'] : null,
            'product_id' => is_array($row['product_id'] ?? null) ? (int) $row['product_id'][0] : null,
            'product_name' => is_array($row['product_id'] ?? null) ? (string) $row['product_id'][1] : null,
            'model' => null,
            'sale' => $sale,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function findSaleForLot(int $uid, int $lotId, string $serialNumber): ?array
    {
        if ($lotId <= 0) {
            return null;
        }

        try {
            $moves = $this->executeKw($uid, 'stock.move.line', 'search_read', [
                [['lot_id', '=', $lotId]],
            ], [
                'fields' => ['id', 'product_id', 'lot_id', 'reference', 'date', 'owner_id', 'location_dest_id'],
                'limit' => 20,
                'order' => 'date desc, id desc',
            ]);

            if ($moves === []) {
                return null;
            }

            $move = $moves[0];

            return [
                'purchase_date' => isset($move['date']) ? substr((string) $move['date'], 0, 10) : null,
                'invoice_number' => $move['reference'] ?? null,
                'branch_name' => is_array($move['location_dest_id'] ?? null) ? $move['location_dest_id'][1] : null,
                'odoo_pos_order_id' => null,
                'customer' => [
                    'full_name' => is_array($move['owner_id'] ?? null) ? $move['owner_id'][1] : null,
                    'mobile_number' => null,
                    'email' => null,
                    'odoo_customer_id' => is_array($move['owner_id'] ?? null) ? $move['owner_id'][0] : null,
                ],
            ];
        } catch (Throwable $e) {
            $this->log('retrieve_sale', 'stock.move.line', 500, $e->getMessage(), 'failed', $serialNumber);

            return null;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function findSaleBySerialName(int $uid, string $serialNumber): ?array
    {
        foreach ($this->serialCandidates($serialNumber) as $candidate) {
            try {
                $moves = $this->executeKw($uid, 'stock.move.line', 'search_read', [
                    [['lot_name', '=', $candidate]],
                ], [
                    'fields' => ['id', 'product_id', 'lot_id', 'lot_name', 'reference', 'date', 'owner_id', 'location_dest_id'],
                    'limit' => 1,
                    'order' => 'date desc, id desc',
                ]);
            } catch (Throwable) {
                $moves = [];
            }

            if ($moves !== []) {
                $move = $moves[0];

                return [
                    'purchase_date' => isset($move['date']) ? substr((string) $move['date'], 0, 10) : null,
                    'invoice_number' => $move['reference'] ?? null,
                    'branch_name' => is_array($move['location_dest_id'] ?? null) ? $move['location_dest_id'][1] : null,
                    'odoo_pos_order_id' => null,
                    'customer' => [
                        'full_name' => is_array($move['owner_id'] ?? null) ? $move['owner_id'][1] : null,
                        'mobile_number' => null,
                        'email' => null,
                        'odoo_customer_id' => is_array($move['owner_id'] ?? null) ? $move['owner_id'][0] : null,
                    ],
                ];
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function saleFromPosOrder(int $uid, int $orderId): ?array
    {
        try {
            $orders = $this->executeKw($uid, 'pos.order', 'search_read', [
                [['id', '=', $orderId]],
            ], [
                'fields' => ['id', 'name', 'date_order', 'partner_id', 'pos_reference', 'config_id'],
                'limit' => 1,
            ]);
        } catch (Throwable) {
            return null;
        }

        if ($orders === []) {
            return null;
        }

        $order = $orders[0];
        $partnerId = is_array($order['partner_id'] ?? null) ? (int) $order['partner_id'][0] : null;
        $customer = [
            'full_name' => is_array($order['partner_id'] ?? null) ? (string) $order['partner_id'][1] : null,
            'mobile_number' => null,
            'email' => null,
            'odoo_customer_id' => $partnerId,
        ];

        if ($partnerId) {
            try {
                $partners = $this->executeKw($uid, 'res.partner', 'search_read', [
                    [['id', '=', $partnerId]],
                ], [
                    'fields' => ['id', 'name', 'phone', 'mobile', 'email'],
                    'limit' => 1,
                ]);
                if ($partners !== []) {
                    $partner = $partners[0];
                    $customer['full_name'] = $this->nullIfEmpty($partner['name'] ?? null) ?: $customer['full_name'];
                    $customer['mobile_number'] = $this->nullIfEmpty($partner['mobile'] ?? null)
                        ?: $this->nullIfEmpty($partner['phone'] ?? null);
                    $customer['email'] = $this->nullIfEmpty($partner['email'] ?? null);
                }
            } catch (Throwable) {
                // Keep basic partner name from the order.
            }
        }

        return [
            'purchase_date' => isset($order['date_order']) ? substr((string) $order['date_order'], 0, 10) : null,
            'invoice_number' => $this->nullIfEmpty($order['pos_reference'] ?? null) ?: $this->nullIfEmpty($order['name'] ?? null),
            'branch_name' => is_array($order['config_id'] ?? null) ? (string) $order['config_id'][1] : null,
            'odoo_pos_order_id' => (string) $orderId,
            'customer' => $customer,
        ];
    }

    /**
     * @return list<string>
     */
    protected function serialCandidates(string $serialNumber): array
    {
        $trimmed = trim($serialNumber);
        $candidates = [
            $trimmed,
            strtoupper($trimmed),
            strtolower($trimmed),
        ];

        return array_values(array_unique(array_filter($candidates, fn (string $value) => $value !== '')));
    }

    protected function nullIfEmpty(mixed $value): ?string
    {
        $string = is_string($value) ? trim($value) : (is_scalar($value) ? trim((string) $value) : '');

        return $string === '' ? null : $string;
    }

    protected function mockSerialLookup(string $serialNumber): array
    {
        $normalized = strtoupper(trim($serialNumber));

        if (str_starts_with($normalized, 'MOCK-MISS') || str_contains($normalized, 'NOTFOUND')) {
            $this->log('validate_serial', 'mock', 404, 'Serial not found', 'not_found', $serialNumber);

            return ['found' => false, 'message' => 'Serial number not found in Odoo.'];
        }

        if (str_starts_with($normalized, 'MOCK-FAIL') || $normalized === 'ODOOFAIL') {
            $this->log('validate_serial', 'mock', 500, 'Simulated Odoo outage', 'failed', $serialNumber);
            throw new \RuntimeException('Simulated Odoo outage');
        }

        $product = Product::query()->where('is_active', true)->first();

        $this->log('validate_serial', 'mock', 200, null, 'success', $serialNumber);

        return [
            'found' => true,
            'message' => 'Serial number validated (mock mode).',
            'product' => [
                'id' => $product?->id,
                'odoo_product_id' => $product?->odoo_product_id ?? 'MOCK-P-100',
                'odoo_serial_id' => 'MOCK-S-'.$normalized,
                'name' => $product?->name ?? 'K-Elec Sample Appliance',
                'model' => $product?->model ?? 'KE-1000',
                'category_id' => $product?->product_category_id,
            ],
            'sale' => [
                'purchase_date' => now()->subDays(5)->toDateString(),
                'invoice_number' => 'INV-MOCK-001',
                'branch_name' => 'Sarin',
                'odoo_pos_order_id' => 'POS-MOCK-001',
            ],
            'customer' => [
                'full_name' => str_starts_with($normalized, 'MOCK-CUST') ? 'Mock Prefill Customer' : null,
                'mobile_number' => str_starts_with($normalized, 'MOCK-CUST') ? '0711111111' : null,
                'email' => str_starts_with($normalized, 'MOCK-CUST') ? 'mock@example.com' : null,
                'odoo_customer_id' => str_starts_with($normalized, 'MOCK-CUST') ? 'MOCK-C-1' : null,
            ],
        ];
    }

    protected function authenticate(): int
    {
        $settings = app(SettingsService::class);
        $baseUrl = $this->baseUrl();

        if ($baseUrl === '') {
            throw new \RuntimeException('Odoo base URL is not configured.');
        }

        $payload = [
            'jsonrpc' => '2.0',
            'method' => 'call',
            'params' => [
                'service' => 'common',
                'method' => 'authenticate',
                'args' => [
                    $settings->get('odoo_database') ?: env('ODOO_DATABASE'),
                    $settings->get('odoo_username') ?: env('ODOO_USERNAME'),
                    $settings->get('odoo_api_key') ?: env('ODOO_API_KEY') ?: env('ODOO_PASSWORD'),
                    new \stdClass,
                ],
            ],
            'id' => time(),
        ];

        $response = Http::timeout((int) $settings->get('odoo_timeout', 15))
            ->acceptJson()
            ->post($baseUrl.'/jsonrpc', $payload);

        if (! $response->successful()) {
            throw new \RuntimeException('Odoo server responded with HTTP '.$response->status().'.');
        }

        if ($response->json('error')) {
            $error = $response->json('error.message')
                ?? $response->json('error.data.message')
                ?? 'JSON-RPC error';

            throw new \RuntimeException((string) $error);
        }

        $result = $response->json('result');

        if ($result === false || $result === null || $result === '') {
            throw new \RuntimeException('Authentication failed. Check database, username, and API key.');
        }

        return (int) $result;
    }

    /**
     * @param  array<int, mixed>  $args
     * @param  array<string, mixed>  $kwargs
     * @return array<int, array<string, mixed>>
     */
    protected function executeKw(int $uid, string $model, string $method, array $args = [], array $kwargs = []): array
    {
        $settings = app(SettingsService::class);
        $rpcArgs = [
            $settings->get('odoo_database') ?: env('ODOO_DATABASE'),
            $uid,
            $settings->get('odoo_api_key') ?: env('ODOO_API_KEY') ?: env('ODOO_PASSWORD'),
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

        $response = Http::timeout((int) ($settings->get('odoo_timeout') ?: env('ODOO_TIMEOUT', 15)))
            ->acceptJson()
            ->post($this->baseUrl().'/jsonrpc', $payload);

        if (! $response->successful()) {
            throw new \RuntimeException('Odoo request failed with HTTP '.$response->status().'.');
        }

        if ($response->json('error')) {
            $message = $response->json('error.data.message')
                ?? $response->json('error.message')
                ?? 'Unknown Odoo error';

            throw new \RuntimeException((string) $message);
        }

        $result = $response->json('result');

        return is_array($result) ? $result : [];
    }

    protected function baseUrl(): string
    {
        $settings = app(SettingsService::class);

        return rtrim((string) ($settings->get('odoo_base_url') ?: env('ODOO_URL') ?: env('ODOO_BASE_URL') ?: ''), '/');
    }

    protected function log(string $action, ?string $endpoint, ?int $status, ?string $error, string $resultStatus, ?string $reference = null): void
    {
        try {
            OdooSyncLog::create([
                'endpoint' => $endpoint,
                'action' => $action,
                'request_reference' => $reference,
                'response_status' => $status,
                'error_message' => $error,
                'status' => $resultStatus,
            ]);
        } catch (Throwable $e) {
            Log::warning('Failed to write Odoo sync log', ['error' => $e->getMessage()]);
        }
    }
}
