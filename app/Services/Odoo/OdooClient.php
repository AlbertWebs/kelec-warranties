<?php

namespace App\Services\Odoo;

use App\Models\OdooSyncLog;
use App\Models\Product;
use App\Services\ActivityLogger;
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
            // Warranty registration validates unit serials/lots only — never catalog barcode/SKU/name.
            $match = $this->findSerialMatch($uid, $serialNumber);

            if ($match === null) {
                $this->log('validate_serial', 'stock.lot|pos.pack.operation.lot', 404, 'Serial not found', 'not_found', $serialNumber);

                return ['found' => false, 'message' => 'Serial number not found in Odoo.'];
            }

            $productId = (int) ($match['product_id'] ?? 0);
            $productDetails = $productId > 0 ? $this->fetchProductDetails($uid, $productId) : null;

            $sale = $match['sale'] ?? null;
            if (! $this->isCustomerSale($sale)) {
                $sale = $this->findCustomerSaleForLot($uid, (int) ($match['lot_id'] ?? 0), $serialNumber);
            }
            if (! $this->isCustomerSale($sale)) {
                $sale = $this->findPosSaleBySerial($uid, $serialNumber);
            }

            // Never fall back to "latest POS sale for this product model" — that belongs to a
            // different unit and often an already-registered warranty.

            $currentLocation = $this->findCurrentLocationForLot($uid, (int) ($match['lot_id'] ?? 0), $serialNumber);

            if (! $this->isCustomerSale($sale)) {
                // Internal transfer / still in warehouse — not a customer purchase yet.
                $sale = [
                    'purchase_date' => null,
                    'invoice_number' => null,
                    'branch_name' => $currentLocation['branch_name'] ?? null,
                    'odoo_pos_order_id' => null,
                    'sale_status' => 'in_stock',
                    'current_location' => $currentLocation['location_label'] ?? null,
                    'customer' => null,
                ];
            } else {
                $sale['sale_status'] = 'sold';
                if (! filled($sale['branch_name'] ?? null) && filled($currentLocation['branch_name'] ?? null)) {
                    $sale['branch_name'] = $currentLocation['branch_name'];
                }
                $sale['current_location'] = $currentLocation['location_label'] ?? ($sale['branch_name'] ?? null);
            }

            // Enrich customer contact only from POS rows that match this exact serial.
            if (($sale['sale_status'] ?? null) === 'sold'
                && $this->customerNeedsContactEnrichment(is_array($sale) ? ($sale['customer'] ?? null) : null)) {
                $posSale = $this->findPosSaleBySerial($uid, $serialNumber);
                if (is_array($posSale)) {
                    $sale = $this->mergeSaleDetails($sale, $posSale);
                    $sale['sale_status'] = 'sold';
                }
            }

            $customer = is_array($sale) ? ($sale['customer'] ?? null) : null;
            if (is_array($sale)) {
                unset($sale['customer']);
            }

            $model = $this->nullIfEmpty($match['model'] ?? null)
                ?: $this->nullIfEmpty($productDetails['default_code'] ?? null)
                ?: $this->nullIfEmpty($productDetails['barcode'] ?? null)
                ?: $this->nullIfEmpty($productDetails['name'] ?? null)
                ?: $this->nullIfEmpty($match['product_name'] ?? null);

            $name = $this->nullIfEmpty($productDetails['display_name'] ?? null)
                ?: $this->nullIfEmpty($productDetails['name'] ?? null)
                ?: $this->nullIfEmpty($match['product_name'] ?? null)
                ?: $model;

            $message = ($sale['sale_status'] ?? null) === 'in_stock'
                ? 'Serial found in stock'
                    .(filled($sale['branch_name'] ?? null) ? ' at '.$sale['branch_name'] : '')
                    .'. This unit has not been sold yet. You can order it from,'
                : 'Serial number validated against Odoo.';

            $this->log('validate_serial', $match['source'], 200, null, 'success', $serialNumber);

            return [
                'found' => true,
                'message' => $message,
                'product' => [
                    'odoo_product_id' => $productId > 0 ? $productId : ($match['product_id'] ?? null),
                    'odoo_serial_id' => $match['lot_id'] ?? null,
                    'name' => $name,
                    'model' => $model,
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
        $productId = is_array($row['product_id'] ?? null) ? (int) $row['product_id'][0] : null;
        $sale = $orderId > 0 ? $this->saleFromPosOrder($uid, $orderId) : [
            'purchase_date' => isset($row['create_date']) ? substr((string) $row['create_date'], 0, 10) : null,
            'invoice_number' => null,
            'branch_name' => null,
            'odoo_pos_order_id' => null,
            'customer' => null,
        ];

        $details = $productId ? $this->fetchProductDetails($uid, $productId) : null;

        return [
            'source' => 'pos.pack.operation.lot',
            'lot_id' => isset($row['id']) ? (int) $row['id'] : null,
            'product_id' => $productId,
            'product_name' => is_array($row['product_id'] ?? null) ? (string) $row['product_id'][1] : null,
            'model' => $details['default_code'] ?? $details['barcode'] ?? null,
            'sale' => $sale,
        ];
    }

    /**
     * Only treat stock moves that deliver to a customer as a sale.
     * Internal warehouse transfers must not invent purchase date / invoice.
     *
     * @return array<string, mixed>|null
     */
    protected function findCustomerSaleForLot(int $uid, int $lotId, string $serialNumber): ?array
    {
        $moves = $this->fetchMoveLinesForLot($uid, $lotId, $serialNumber);
        if ($moves === []) {
            return null;
        }

        foreach ($moves as $move) {
            $destLabel = is_array($move['location_dest_id'] ?? null) ? (string) $move['location_dest_id'][1] : null;
            if (! $this->isCustomerLocationLabel($destLabel)) {
                continue;
            }

            $ownerId = is_array($move['owner_id'] ?? null) ? (int) $move['owner_id'][0] : null;
            $ownerLabel = is_array($move['owner_id'] ?? null) ? (string) $move['owner_id'][1] : null;

            return [
                'purchase_date' => isset($move['date']) ? substr((string) $move['date'], 0, 10) : null,
                'invoice_number' => $this->nullIfEmpty($move['reference'] ?? null),
                'branch_name' => $this->branchFromStockLocations($move),
                'odoo_pos_order_id' => null,
                'sale_status' => 'sold',
                'customer' => $this->customerFromPartner($uid, $ownerId, $ownerLabel),
            ];
        }

        return null;
    }

    /**
     * @return array{branch_name: string|null, location_label: string|null}
     */
    protected function findCurrentLocationForLot(int $uid, int $lotId, string $serialNumber): array
    {
        $empty = ['branch_name' => null, 'location_label' => null];
        if ($lotId <= 0) {
            return $empty;
        }

        try {
            $quants = $this->executeKw($uid, 'stock.quant', 'search_read', [
                [
                    ['lot_id', '=', $lotId],
                    ['quantity', '>', 0],
                ],
            ], [
                'fields' => ['id', 'location_id', 'quantity'],
                'limit' => 5,
                'order' => 'quantity desc, id desc',
            ]);

            foreach ($quants as $quant) {
                $label = is_array($quant['location_id'] ?? null) ? (string) $quant['location_id'][1] : null;
                if ($this->isCustomerLocationLabel($label) || $this->isIgnoredStockLocationLabel($label)) {
                    continue;
                }

                return [
                    'location_label' => $label,
                    'branch_name' => $this->shopNameFromLocationLabel($label),
                ];
            }
        } catch (Throwable $e) {
            $this->log('current_location', 'stock.quant', 500, $e->getMessage(), 'failed', $serialNumber);
        }

        $moves = $this->fetchMoveLinesForLot($uid, $lotId, $serialNumber);
        foreach ($moves as $move) {
            foreach (['location_dest_id', 'location_id'] as $field) {
                $label = is_array($move[$field] ?? null) ? (string) $move[$field][1] : null;
                if ($this->isCustomerLocationLabel($label) || $this->isIgnoredStockLocationLabel($label)) {
                    continue;
                }

                return [
                    'location_label' => $label,
                    'branch_name' => $this->shopNameFromLocationLabel($label),
                ];
            }
        }

        return $empty;
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function fetchMoveLinesForLot(int $uid, int $lotId, string $serialNumber): array
    {
        if ($lotId > 0) {
            try {
                $moves = $this->executeKw($uid, 'stock.move.line', 'search_read', [
                    [['lot_id', '=', $lotId]],
                ], [
                    'fields' => ['id', 'product_id', 'lot_id', 'reference', 'date', 'owner_id', 'location_id', 'location_dest_id'],
                    'limit' => 30,
                    'order' => 'date desc, id desc',
                ]);

                if (is_array($moves) && $moves !== []) {
                    return $moves;
                }
            } catch (Throwable $e) {
                $this->log('retrieve_sale', 'stock.move.line', 500, $e->getMessage(), 'failed', $serialNumber);
            }
        }

        foreach ($this->serialCandidates($serialNumber) as $candidate) {
            try {
                $moves = $this->executeKw($uid, 'stock.move.line', 'search_read', [
                    [['lot_name', '=', $candidate]],
                ], [
                    'fields' => ['id', 'product_id', 'lot_id', 'lot_name', 'reference', 'date', 'owner_id', 'location_id', 'location_dest_id'],
                    'limit' => 30,
                    'order' => 'date desc, id desc',
                ]);
            } catch (Throwable) {
                $moves = [];
            }

            if (is_array($moves) && $moves !== []) {
                return $moves;
            }
        }

        return [];
    }

    /**
     * @deprecated Prefer findCustomerSaleForLot — kept name unused callers may reference.
     *
     * @return array<string, mixed>|null
     */
    protected function findSaleForLot(int $uid, int $lotId, string $serialNumber): ?array
    {
        return $this->findCustomerSaleForLot($uid, $lotId, $serialNumber);
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function findSaleBySerialName(int $uid, string $serialNumber): ?array
    {
        return $this->findCustomerSaleForLot($uid, 0, $serialNumber);
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
                'fields' => ['id', 'name', 'date_order', 'partner_id', 'pos_reference', 'config_id', 'account_move'],
                'limit' => 1,
            ]);
        } catch (Throwable) {
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
        }

        if ($orders === []) {
            return null;
        }

        $order = $orders[0];
        $partnerId = is_array($order['partner_id'] ?? null) ? (int) $order['partner_id'][0] : null;
        $partnerLabel = is_array($order['partner_id'] ?? null) ? (string) $order['partner_id'][1] : null;

        // Fall back to the linked invoice partner when the POS order has no useful customer.
        if ((! $partnerId || $this->isGenericPosPartnerName($partnerLabel)) && ! empty($order['account_move'])) {
            $invoicePartner = $this->partnerIdFromAccountMove($uid, $order['account_move']);
            if ($invoicePartner) {
                $partnerId = $invoicePartner;
            }
        }

        $customer = $this->customerFromPartner($uid, $partnerId, $partnerLabel);

        $invoice = $this->nullIfEmpty($order['pos_reference'] ?? null)
            ?: $this->nullIfEmpty($order['name'] ?? null);

        // Prefer linked accounting invoice/receipt number when available.
        $accountMove = $order['account_move'] ?? null;
        if (is_array($accountMove) && ! empty($accountMove[1])) {
            $invoice = (string) $accountMove[1];
        } elseif (is_numeric($accountMove) && (int) $accountMove > 0) {
            try {
                $moves = $this->executeKw($uid, 'account.move', 'search_read', [
                    [['id', '=', (int) $accountMove]],
                ], [
                    'fields' => ['id', 'name', 'ref', 'partner_id'],
                    'limit' => 1,
                ]);
                if ($moves !== []) {
                    $invoice = $this->nullIfEmpty($moves[0]['name'] ?? null)
                        ?: $this->nullIfEmpty($moves[0]['ref'] ?? null)
                        ?: $invoice;

                    if ((! $customer || $this->isGenericPosPartnerName($customer['full_name'] ?? null))
                        && is_array($moves[0]['partner_id'] ?? null)) {
                        $customer = $this->customerFromPartner(
                            $uid,
                            (int) $moves[0]['partner_id'][0],
                            (string) ($moves[0]['partner_id'][1] ?? '')
                        ) ?: $customer;
                    }
                }
            } catch (Throwable) {
                // Keep POS reference/name.
            }
        }

        return [
            'purchase_date' => isset($order['date_order']) ? substr((string) $order['date_order'], 0, 10) : null,
            'invoice_number' => $invoice,
            'branch_name' => $this->branchFromPosConfigName(
                is_array($order['config_id'] ?? null) ? (string) $order['config_id'][1] : null
            ),
            'odoo_pos_order_id' => (string) $orderId,
            'customer' => $customer,
        ];
    }

    protected function branchFromPosConfigName(?string $configName): ?string
    {
        $name = trim((string) $configName);
        if ($name === '') {
            return null;
        }

        $cleaned = preg_replace('/^(pos|point of sale|kelec|k-elec|brand shop)[\s\-\/:|]*/i', '', $name) ?? $name;
        $cleaned = trim($cleaned, " \t\n\r\0\x0B-|/");

        return $cleaned !== '' ? $cleaned : $name;
    }

    /**
     * @param  array<string, mixed>  $move
     */
    protected function branchFromStockLocations(array $move): ?string
    {
        $candidates = [];
        foreach (['location_id', 'location_dest_id'] as $field) {
            if (is_array($move[$field] ?? null) && ! empty($move[$field][1])) {
                $candidates[] = (string) $move[$field][1];
            }
        }

        foreach ($candidates as $label) {
            if ($this->isCustomerLocationLabel($label) || $this->isIgnoredStockLocationLabel($label)) {
                continue;
            }

            return $this->shopNameFromLocationLabel($label);
        }

        return null;
    }

    protected function shopNameFromLocationLabel(?string $label): ?string
    {
        $label = trim((string) $label);
        if ($label === '') {
            return null;
        }

        // Prefer warehouse / shop-looking labels (often "Sarin/Stock").
        $shop = preg_replace('/\/.*$/', '', $label) ?? $label;
        $shop = trim($shop);

        return $shop !== '' ? $shop : $label;
    }

    protected function isCustomerLocationLabel(?string $label): bool
    {
        $lower = strtolower(trim((string) $label));
        if ($lower === '') {
            return false;
        }

        return str_contains($lower, 'customer')
            || str_contains($lower, 'partners/customers')
            || str_contains($lower, 'partner/customers');
    }

    protected function isIgnoredStockLocationLabel(?string $label): bool
    {
        $lower = strtolower(trim((string) $label));
        if ($lower === '') {
            return true;
        }

        return str_contains($lower, 'vendor')
            || str_contains($lower, 'inventory adjustment')
            || str_contains($lower, 'virtual')
            || $lower === 'stock';
    }

    /**
     * @param  array<string, mixed>|null  $sale
     */
    protected function isCustomerSale(?array $sale): bool
    {
        if (! is_array($sale)) {
            return false;
        }

        if (($sale['sale_status'] ?? null) === 'in_stock') {
            return false;
        }

        return filled($sale['odoo_pos_order_id'] ?? null)
            || filled($sale['purchase_date'] ?? null)
            || filled($sale['invoice_number'] ?? null);
    }

    /**
     * @return array{full_name: string|null, mobile_number: string|null, email: string|null, county: string|null, town: string|null, odoo_customer_id: int|null}|null
     */
    protected function customerFromPartner(int $uid, ?int $partnerId, ?string $fallbackName = null): ?array
    {
        if (! $partnerId || $partnerId <= 0) {
            if ($this->isGenericPosPartnerName($fallbackName)) {
                return null;
            }

            $name = $this->nullIfEmpty($fallbackName);
            if (! $name) {
                return null;
            }

            return [
                'full_name' => $name,
                'mobile_number' => null,
                'email' => null,
                'county' => null,
                'town' => null,
                'odoo_customer_id' => null,
            ];
        }

        $partner = $this->readPartnerRecord($uid, $partnerId);
        if ($partner === null) {
            if ($this->isGenericPosPartnerName($fallbackName)) {
                return null;
            }

            return [
                'full_name' => $this->nullIfEmpty($fallbackName),
                'mobile_number' => null,
                'email' => null,
                'county' => null,
                'town' => null,
                'odoo_customer_id' => $partnerId,
            ];
        }

        $customer = $this->mapPartnerToCustomer($partner, $fallbackName, $partnerId);
        if ($customer === null) {
            return null;
        }

        // Contact records often keep phone/address on the commercial/parent partner.
        if (! filled($customer['mobile_number'] ?? null) || ! filled($customer['town'] ?? null) || ! filled($customer['county'] ?? null)) {
            $relatedIds = [];
            foreach (['commercial_partner_id', 'parent_id'] as $relation) {
                if (is_array($partner[$relation] ?? null) && (int) $partner[$relation][0] > 0) {
                    $relatedIds[] = (int) $partner[$relation][0];
                }
            }

            foreach (array_unique($relatedIds) as $relatedId) {
                if ($relatedId === $partnerId) {
                    continue;
                }

                $related = $this->readPartnerRecord($uid, $relatedId);
                if ($related === null) {
                    continue;
                }

                $relatedCustomer = $this->mapPartnerToCustomer($related, null, $relatedId);
                if ($relatedCustomer === null) {
                    continue;
                }

                $customer = $this->mergeCustomerDetails($customer, $relatedCustomer);
            }
        }

        return $customer;
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function readPartnerRecord(int $uid, int $partnerId): ?array
    {
        $fieldSets = [
            $this->partnerContactFields($uid),
            ['id', 'name', 'phone', 'email', 'street', 'street2', 'city', 'state_id', 'zip', 'commercial_partner_id', 'parent_id'],
            ['id', 'name', 'phone', 'email', 'city', 'state_id'],
            ['id', 'name', 'phone', 'email'],
            ['id', 'name', 'phone'],
            ['id', 'name'],
        ];

        foreach ($fieldSets as $fields) {
            try {
                $partners = $this->executeKw($uid, 'res.partner', 'search_read', [
                    [['id', '=', $partnerId]],
                ], [
                    'fields' => array_values(array_unique($fields)),
                    'limit' => 1,
                ]);
            } catch (Throwable) {
                continue;
            }

            if ($partners !== []) {
                return $partners[0];
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    protected function partnerContactFields(int $uid): array
    {
        static $fieldsByUid = [];

        if (isset($fieldsByUid[$uid])) {
            return $fieldsByUid[$uid];
        }

        $wanted = [
            'id',
            'name',
            'phone',
            'mobile',
            'phone_sanitized',
            'phone_formatted',
            'email',
            'street',
            'street2',
            'city',
            'state_id',
            'zip',
            'contact_address',
            'commercial_partner_id',
            'parent_id',
        ];

        try {
            $available = $this->executeKw($uid, 'res.partner', 'fields_get', [], [
                'attributes' => ['type'],
            ]);
            $fieldsByUid[$uid] = array_values(array_filter(
                $wanted,
                fn (string $field) => array_key_exists($field, $available)
            ));
        } catch (Throwable) {
            // Modern Odoo often has phone but not mobile — never request unknown fields.
            $fieldsByUid[$uid] = [
                'id',
                'name',
                'phone',
                'email',
                'street',
                'street2',
                'city',
                'state_id',
                'zip',
                'contact_address',
                'commercial_partner_id',
                'parent_id',
            ];
        }

        return $fieldsByUid[$uid];
    }

    /**
     * @param  array<string, mixed>  $partner
     * @return array{full_name: string|null, mobile_number: string|null, email: string|null, county: string|null, town: string|null, odoo_customer_id: int|null}|null
     */
    protected function mapPartnerToCustomer(array $partner, ?string $fallbackName, int $partnerId): ?array
    {
        $fullName = $this->nullIfEmpty($partner['name'] ?? null) ?: $this->nullIfEmpty($fallbackName);
        if ($this->isGenericPosPartnerName($fullName)) {
            return null;
        }

        $mobile = $this->nullIfEmpty($partner['mobile'] ?? null)
            ?: $this->nullIfEmpty($partner['phone'] ?? null)
            ?: $this->nullIfEmpty($partner['phone_sanitized'] ?? null)
            ?: $this->nullIfEmpty($partner['phone_formatted'] ?? null);

        $county = is_array($partner['state_id'] ?? null)
            ? $this->nullIfEmpty($partner['state_id'][1] ?? null)
            : null;

        $town = $this->nullIfEmpty($partner['city'] ?? null)
            ?: $this->nullIfEmpty($partner['street'] ?? null)
            ?: $this->nullIfEmpty($partner['street2'] ?? null);

        // Last resort: first non-empty line from the complete address block.
        if (! $town) {
            $contactAddress = $this->nullIfEmpty($partner['contact_address'] ?? null);
            if ($contactAddress) {
                $lines = preg_split('/\r\n|\r|\n/', $contactAddress) ?: [];
                foreach ($lines as $line) {
                    $line = trim((string) $line);
                    if ($line === '' || strcasecmp($line, (string) $fullName) === 0) {
                        continue;
                    }
                    $town = $line;
                    break;
                }
            }
        }

        return [
            'full_name' => $fullName,
            'mobile_number' => $mobile,
            'email' => $this->nullIfEmpty($partner['email'] ?? null),
            'county' => $county,
            'town' => $town,
            'odoo_customer_id' => $partnerId,
        ];
    }

    /**
     * @param  array{full_name: string|null, mobile_number: string|null, email: string|null, county: string|null, town: string|null, odoo_customer_id: int|null}  $base
     * @param  array{full_name: string|null, mobile_number: string|null, email: string|null, county: string|null, town: string|null, odoo_customer_id: int|null}  $extra
     * @return array{full_name: string|null, mobile_number: string|null, email: string|null, county: string|null, town: string|null, odoo_customer_id: int|null}
     */
    protected function mergeCustomerDetails(array $base, array $extra): array
    {
        foreach (['full_name', 'mobile_number', 'email', 'county', 'town'] as $field) {
            if (! filled($base[$field] ?? null) && filled($extra[$field] ?? null)) {
                $base[$field] = $extra[$field];
            }
        }

        return $base;
    }

    protected function partnerIdFromAccountMove(int $uid, mixed $accountMove): ?int
    {
        $moveId = null;
        if (is_array($accountMove) && isset($accountMove[0])) {
            $moveId = (int) $accountMove[0];
        } elseif (is_numeric($accountMove)) {
            $moveId = (int) $accountMove;
        }

        if (! $moveId) {
            return null;
        }

        try {
            $moves = $this->executeKw($uid, 'account.move', 'search_read', [
                [['id', '=', $moveId]],
            ], [
                'fields' => ['id', 'partner_id'],
                'limit' => 1,
            ]);
        } catch (Throwable) {
            return null;
        }

        if ($moves === [] || ! is_array($moves[0]['partner_id'] ?? null)) {
            return null;
        }

        return (int) $moves[0]['partner_id'][0];
    }

    protected function isGenericPosPartnerName(?string $name): bool
    {
        $normalized = strtolower(trim((string) $name));
        if ($normalized === '') {
            return true;
        }

        $exact = [
            'customer',
            'guest',
            'public',
            'anonymous',
        ];

        if (in_array($normalized, $exact, true)) {
            return true;
        }

        $partials = [
            'walk-in',
            'walk in',
            'walking customer',
            'pos customer',
            'cash customer',
            'retail customer',
            'walk-in customer',
        ];

        foreach ($partials as $partial) {
            if (str_contains($normalized, $partial)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>|null  $customer
     */
    protected function customerNeedsContactEnrichment(?array $customer): bool
    {
        if (! $this->customerHasUsefulDetails($customer)) {
            return true;
        }

        return ! filled($customer['mobile_number'] ?? null)
            || (! filled($customer['county'] ?? null) && ! filled($customer['town'] ?? null));
    }

    /**
     * @param  array<string, mixed>|null  $customer
     */
    protected function customerHasUsefulDetails(?array $customer): bool
    {
        if (! is_array($customer)) {
            return false;
        }

        if ($this->isGenericPosPartnerName($customer['full_name'] ?? null)
            && ! filled($customer['mobile_number'] ?? null)
            && ! filled($customer['email'] ?? null)) {
            return false;
        }

        return filled($customer['full_name'] ?? null)
            || filled($customer['mobile_number'] ?? null)
            || filled($customer['email'] ?? null);
    }

    /**
     * @param  array<string, mixed>|null  $sale
     */
    protected function saleHasDetails(?array $sale): bool
    {
        if (! is_array($sale)) {
            return false;
        }

        return filled($sale['purchase_date'] ?? null) || filled($sale['invoice_number'] ?? null);
    }

    /**
     * Prefer filled fields from $preferred while keeping any existing values from $base.
     *
     * @param  array<string, mixed>|null  $base
     * @param  array<string, mixed>  $preferred
     * @return array<string, mixed>
     */
    protected function mergeSaleDetails(?array $base, array $preferred): array
    {
        if (! is_array($base)) {
            return $preferred;
        }

        $merged = $base;
        foreach (['purchase_date', 'invoice_number', 'branch_name', 'odoo_pos_order_id'] as $field) {
            if (filled($preferred[$field] ?? null)) {
                $merged[$field] = $preferred[$field];
            }
        }

        $preferredCustomer = is_array($preferred['customer'] ?? null) ? $preferred['customer'] : null;
        $baseCustomer = is_array($merged['customer'] ?? null) ? $merged['customer'] : null;

        if ($this->customerHasUsefulDetails($preferredCustomer) || $this->customerHasUsefulDetails($baseCustomer)) {
            $empty = [
                'full_name' => null,
                'mobile_number' => null,
                'email' => null,
                'county' => null,
                'town' => null,
                'odoo_customer_id' => null,
            ];

            // Prefer POS/preferred contact values, then fill any gaps from the earlier sale.
            $merged['customer'] = $this->mergeCustomerDetails(
                $this->mergeCustomerDetails($empty, $preferredCustomer ?: []),
                $baseCustomer ?: []
            );
        } elseif (! array_key_exists('customer', $merged)) {
            $merged['customer'] = $preferredCustomer;
        }

        return $merged;
    }

    /**
     * @return array{source: string, lot_id: int|null, product_id: int|null, product_name: string|null, model: string|null, sale?: array<string, mixed>|null}|null
     */
    protected function findProductCodeMatch(int $uid, string $serialNumber): ?array
    {
        foreach ($this->serialCandidates($serialNumber) as $candidate) {
            foreach (['default_code', 'barcode', 'name'] as $field) {
                try {
                    $products = $this->executeKw($uid, 'product.product', 'search_read', [
                        [[$field, '=', $candidate]],
                    ], [
                        'fields' => ['id', 'name', 'display_name', 'default_code', 'barcode'],
                        'limit' => 1,
                        'order' => 'id desc',
                    ]);
                } catch (Throwable) {
                    $products = [];
                }

                if ($products === []) {
                    continue;
                }

                $product = $products[0];
                $productId = (int) ($product['id'] ?? 0);
                $sale = $productId > 0 ? $this->findLatestPosSaleForProduct($uid, $productId) : null;

                return [
                    'source' => 'product.product',
                    'lot_id' => null,
                    'product_id' => $productId > 0 ? $productId : null,
                    'product_name' => $this->nullIfEmpty($product['display_name'] ?? null) ?: $this->nullIfEmpty($product['name'] ?? null),
                    'model' => $this->nullIfEmpty($product['default_code'] ?? null) ?: $this->nullIfEmpty($product['barcode'] ?? null),
                    'sale' => $sale,
                ];
            }
        }

        return null;
    }

    /**
     * @return array{id: int, name: string|null, display_name: string|null, default_code: string|null, barcode: string|null}|null
     */
    protected function fetchProductDetails(int $uid, int $productId): ?array
    {
        if ($productId <= 0) {
            return null;
        }

        try {
            $products = $this->executeKw($uid, 'product.product', 'search_read', [
                [['id', '=', $productId]],
            ], [
                'fields' => ['id', 'name', 'display_name', 'default_code', 'barcode'],
                'limit' => 1,
            ]);
        } catch (Throwable) {
            return null;
        }

        if ($products === []) {
            return null;
        }

        $product = $products[0];

        return [
            'id' => (int) $product['id'],
            'name' => $this->nullIfEmpty($product['name'] ?? null),
            'display_name' => $this->nullIfEmpty($product['display_name'] ?? null),
            'default_code' => $this->nullIfEmpty($product['default_code'] ?? null),
            'barcode' => $this->nullIfEmpty($product['barcode'] ?? null),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function findPosSaleBySerial(int $uid, string $serialNumber): ?array
    {
        $packMatch = $this->findPosPackLotMatch($uid, $serialNumber);
        if ($this->saleHasDetails($packMatch['sale'] ?? null)) {
            return $packMatch['sale'];
        }

        // Some POS setups keep serial text on the order line note/full name.
        foreach ($this->serialCandidates($serialNumber) as $candidate) {
            foreach (['full_product_name', 'customer_note', 'note'] as $field) {
                try {
                    $lines = $this->executeKw($uid, 'pos.order.line', 'search_read', [
                        [[$field, 'ilike', $candidate]],
                    ], [
                        'fields' => ['id', 'order_id', 'product_id', $field],
                        'limit' => 5,
                        'order' => 'id desc',
                    ]);
                } catch (Throwable) {
                    continue;
                }

                foreach ($lines as $line) {
                    $orderId = is_array($line['order_id'] ?? null) ? (int) $line['order_id'][0] : 0;
                    if ($orderId <= 0) {
                        continue;
                    }
                    $sale = $this->saleFromPosOrder($uid, $orderId);
                    if ($this->saleHasDetails($sale)) {
                        return $sale;
                    }
                }
            }
        }

        return is_array($packMatch['sale'] ?? null) ? $packMatch['sale'] : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function findLatestPosSaleForProduct(int $uid, int $productId): ?array
    {
        if ($productId <= 0) {
            return null;
        }

        // Prefer an explicit POS pack-lot row for this product (unique unit), newest first.
        try {
            $packs = $this->executeKw($uid, 'pos.pack.operation.lot', 'search_read', [
                [['product_id', '=', $productId]],
            ], [
                'fields' => ['id', 'lot_name', 'product_id', 'order_id', 'create_date'],
                'limit' => 10,
                'order' => 'id desc',
            ]);

            foreach ($packs as $pack) {
                $orderId = is_array($pack['order_id'] ?? null) ? (int) $pack['order_id'][0] : 0;
                if ($orderId <= 0) {
                    continue;
                }
                $sale = $this->saleFromPosOrder($uid, $orderId);
                if ($this->saleHasDetails($sale)) {
                    return $sale;
                }
            }
        } catch (Throwable) {
            // Fall through to order lines.
        }

        try {
            $lines = $this->executeKw($uid, 'pos.order.line', 'search_read', [
                [['product_id', '=', $productId]],
            ], [
                'fields' => ['id', 'order_id', 'product_id', 'qty', 'full_product_name'],
                'limit' => 10,
                'order' => 'id desc',
            ]);
        } catch (Throwable) {
            return null;
        }

        foreach ($lines as $line) {
            $orderId = is_array($line['order_id'] ?? null) ? (int) $line['order_id'][0] : 0;
            if ($orderId <= 0) {
                continue;
            }
            $sale = $this->saleFromPosOrder($uid, $orderId);
            if ($this->saleHasDetails($sale)) {
                return $sale;
            }
        }

        return null;
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

        if (str_starts_with($normalized, 'MOCK-STOCK') || str_contains($normalized, 'TRANSFER')) {
            $product = Product::query()->where('is_active', true)->first();

            $this->log('validate_serial', 'mock', 200, null, 'success', $serialNumber);

            return [
                'found' => true,
                'message' => 'Serial found in stock at CBD. This unit has not been sold yet. You can order it from,',
                'product' => [
                    'id' => $product?->id,
                    'odoo_product_id' => $product?->odoo_product_id ?? 'MOCK-P-100',
                    'odoo_serial_id' => 'MOCK-S-'.$normalized,
                    'name' => $product?->name ?? 'K-Elec Sample Appliance',
                    'model' => $product?->model ?? 'KE-1000',
                    'category_id' => $product?->product_category_id,
                ],
                'sale' => [
                    'purchase_date' => null,
                    'invoice_number' => null,
                    'branch_name' => 'CBD',
                    'odoo_pos_order_id' => null,
                    'sale_status' => 'in_stock',
                    'current_location' => 'CBD/Stock',
                ],
                'customer' => null,
            ];
        }

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
                'sale_status' => 'sold',
                'current_location' => 'Sarin',
            ],
            'customer' => [
                'full_name' => str_starts_with($normalized, 'MOCK-CUST') ? 'Mock Prefill Customer' : null,
                'mobile_number' => str_starts_with($normalized, 'MOCK-CUST') ? '0711111111' : null,
                'email' => str_starts_with($normalized, 'MOCK-CUST') ? 'mock@example.com' : null,
                'county' => str_starts_with($normalized, 'MOCK-CUST') ? 'Nairobi' : null,
                'town' => str_starts_with($normalized, 'MOCK-CUST') ? 'Westlands' : null,
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

        try {
            app(ActivityLogger::class)->log(
                type: 'odoo_fetch',
                action: $action,
                status: in_array($resultStatus, ['success', 'found'], true) ? 'success' : (in_array($resultStatus, ['failed', 'error'], true) ? 'failed' : $resultStatus),
                query: $reference,
                reference: $endpoint,
                resultSummary: $error ?: ('Odoo '.$action.' '.$resultStatus),
                meta: [
                    'endpoint' => $endpoint,
                    'response_status' => $status,
                    'odoo_status' => $resultStatus,
                ],
            );
        } catch (Throwable $e) {
            Log::warning('Failed to write Odoo activity log', ['error' => $e->getMessage()]);
        }
    }
}
