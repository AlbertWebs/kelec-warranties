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
        return (bool) app(SettingsService::class)->get('odoo_enabled', false);
    }

    public function mockMode(): bool
    {
        return (bool) app(SettingsService::class)->get('odoo_mock_mode', true);
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

        if ($baseUrl === '' || $database === '' || $username === '' || $apiKey === '') {
            $this->log('authenticate', $baseUrl !== '' ? $baseUrl.'/jsonrpc' : null, null, 'Incomplete Odoo credentials', 'failed');

            return [
                'ok' => false,
                'flash' => 'error',
                'message' => $this->mockMode()
                    ? 'Mock mode is enabled and Odoo is not configured. Add Base URL, database, username, and API key in Settings to test a live connection.'
                    : 'Odoo connection failed: Base URL, database, username, and API key are required.',
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
     * @return array{found: bool, product?: array<string, mixed>, sale?: array<string, mixed>, customer?: array<string, mixed>, message: string}
     */
    public function validateSerial(string $serialNumber): array
    {
        if ($this->mockMode()) {
            return $this->mockSerialLookup($serialNumber);
        }

        if (! $this->enabled()) {
            throw new \RuntimeException('Odoo integration is disabled.');
        }

        try {
            $uid = $this->authenticate();
            $lots = $this->executeKw($uid, 'stock.lot', 'search_read', [
                [['name', '=', $serialNumber]],
                ['id', 'name', 'product_id', 'ref'],
            ]);

            if ($lots === []) {
                // Fallback for older Odoo versions.
                $lots = $this->executeKw($uid, 'stock.production.lot', 'search_read', [
                    [['name', '=', $serialNumber]],
                    ['id', 'name', 'product_id', 'ref'],
                ]);
            }

            if ($lots === []) {
                $this->log('validate_serial', 'stock.lot', 404, 'Serial not found', 'not_found', $serialNumber);

                return ['found' => false, 'message' => 'Serial number not found in Odoo.'];
            }

            $lot = $lots[0];
            $productId = is_array($lot['product_id'] ?? null) ? $lot['product_id'][0] : null;
            $productName = is_array($lot['product_id'] ?? null) ? $lot['product_id'][1] : null;

            $sale = $this->findSaleForLot($uid, (int) ($lot['id'] ?? 0), $serialNumber);
            $customer = $sale['customer'] ?? null;
            unset($sale['customer']);

            $this->log('validate_serial', 'stock.lot', 200, null, 'success', $serialNumber);

            return [
                'found' => true,
                'message' => 'Serial number validated against Odoo.',
                'product' => [
                    'odoo_product_id' => $productId,
                    'odoo_serial_id' => $lot['id'] ?? null,
                    'name' => $productName,
                    'model' => null,
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
                ['id', 'product_id', 'lot_id', 'reference', 'date', 'owner_id', 'location_dest_id'],
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
                    $settings->get('odoo_database'),
                    $settings->get('odoo_username'),
                    $settings->get('odoo_api_key'),
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
     * @return array<int, array<string, mixed>>
     */
    protected function executeKw(int $uid, string $model, string $method, array $args = []): array
    {
        $settings = app(SettingsService::class);
        $payload = [
            'jsonrpc' => '2.0',
            'method' => 'call',
            'params' => [
                'service' => 'object',
                'method' => 'execute_kw',
                'args' => [
                    $settings->get('odoo_database'),
                    $uid,
                    $settings->get('odoo_api_key'),
                    $model,
                    $method,
                    $args,
                ],
            ],
            'id' => time(),
        ];

        $response = Http::timeout((int) $settings->get('odoo_timeout', 15))
            ->post($this->baseUrl().'/jsonrpc', $payload);

        if (! $response->successful()) {
            throw new \RuntimeException('Odoo request failed.');
        }

        return $response->json('result') ?? [];
    }

    protected function baseUrl(): string
    {
        return rtrim((string) app(SettingsService::class)->get('odoo_base_url', ''), '/');
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
