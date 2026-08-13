<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Warranty;
use App\Services\Odoo\OdooProductService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProductLookupService
{
    public function __construct(
        protected OdooProductService $odooProductService,
        protected ActivityLogger $activityLogger,
    ) {}

    /**
     * @return array{success: bool, source?: string, message: string, product?: Product, purchase_date?: string|null, is_registered?: bool, warranty_reference?: string|null, odoo_unavailable?: bool}
     */
    public function lookup(string $query): array
    {
        $query = trim($query);
        if ($query === '') {
            $result = ['success' => false, 'message' => 'Please provide a valid search value.'];
            $this->logLookup($query, $result);

            return $result;
        }

        $local = $this->findLocal($query);
        if ($local) {
            $registration = $this->resolveRegistrationStatus($query, $local);

            $result = [
                'success' => true,
                'source' => 'local',
                'message' => 'Product found.',
                'product' => $local,
                'purchase_date' => $this->resolvePurchaseDate($query, $local),
                'is_registered' => $registration['is_registered'],
                'warranty_reference' => $registration['warranty_reference'],
            ];
            $this->logLookup($query, $result);

            return $result;
        }

        $negativeKey = 'product_lookup_negative:'.md5(strtolower($query));
        if (Cache::has($negativeKey)) {
            $result = [
                'success' => false,
                'message' => 'We could not find a product matching the information provided.',
                'is_registered' => false,
            ];
            $this->logLookup($query, $result, ['cache' => 'negative']);

            return $result;
        }

        try {
            $odooProduct = $this->odooProductService->searchProduct($query);
            if (! $odooProduct) {
                Cache::put($negativeKey, true, now()->addMinutes(5));

                $result = [
                    'success' => false,
                    'message' => 'We could not find a product matching the information provided.',
                    'is_registered' => false,
                ];
                $this->logLookup($query, $result, ['source' => 'odoo']);

                return $result;
            }

            $product = $this->odooProductService->upsertProductFromOdoo($odooProduct);
            $registration = $this->resolveRegistrationStatus($query, $product);

            $result = [
                'success' => true,
                'source' => 'odoo',
                'message' => 'Product found.',
                'product' => $product,
                'purchase_date' => $this->resolvePurchaseDate($query, $product),
                'is_registered' => $registration['is_registered'],
                'warranty_reference' => $registration['warranty_reference'],
            ];
            $this->logLookup($query, $result);

            return $result;
        } catch (Throwable $e) {
            Log::warning('Product lookup Odoo fallback failed', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);

            $result = [
                'success' => false,
                'odoo_unavailable' => true,
                'message' => 'We could not complete the product lookup at the moment. Please try again shortly.',
            ];
            $this->logLookup($query, $result, ['error' => $e->getMessage()]);

            return $result;
        }
    }

    /**
     * @param  array<string, mixed>  $result
     * @param  array<string, mixed>  $extra
     */
    protected function logLookup(string $query, array $result, array $extra = []): void
    {
        $product = $result['product'] ?? null;

        $this->activityLogger->log(
            type: 'product_lookup',
            action: 'lookup',
            status: ($result['success'] ?? false)
                ? 'found'
                : (($result['odoo_unavailable'] ?? false) ? 'error' : 'not_found'),
            query: $query !== '' ? $query : null,
            reference: $result['warranty_reference'] ?? ($product instanceof Product ? (string) $product->id : null),
            resultSummary: $result['message'] ?? null,
            meta: array_filter([
                'source' => $result['source'] ?? ($extra['source'] ?? null),
                'product_id' => $product instanceof Product ? $product->id : null,
                'product_name' => $product instanceof Product ? $product->customerFacingName() : null,
                'is_registered' => $result['is_registered'] ?? null,
                'cache' => $extra['cache'] ?? null,
                'error' => $extra['error'] ?? null,
            ], fn ($value) => $value !== null && $value !== ''),
        );
    }

    protected function findLocal(string $query): ?Product
    {
        $query = trim($query);

        $exact = Product::query()
            ->where('serial_number', $query)
            ->orWhere('barcode', $query)
            ->orWhere('default_code', $query)
            ->orWhere('sku', $query)
            ->orWhere('product_code', $query)
            ->orWhere('odoo_id', $query)
            ->orWhere('odoo_product_id', $query)
            ->first();

        if ($exact) {
            return $exact;
        }

        return Product::query()
            ->where('name', 'like', '%'.$query.'%')
            ->orWhere('display_name', 'like', '%'.$query.'%')
            ->latest('last_synced_at')
            ->first();
    }

    protected function resolvePurchaseDate(string $query, Product $product): ?string
    {
        $serials = array_values(array_unique(array_filter([
            $query,
            $product->serial_number,
        ])));

        foreach ($serials as $serial) {
            $warrantyDate = Warranty::query()
                ->where('serial_number', $serial)
                ->whereNotNull('purchase_date')
                ->latest('id')
                ->value('purchase_date');

            if ($warrantyDate) {
                return optional($warrantyDate)->toDateString() ?? (string) $warrantyDate;
            }
        }

        foreach ($serials as $serial) {
            try {
                $odoo = $this->odooProductService->lookupBySerial($serial);
                if (($odoo['found'] ?? false) && ! empty($odoo['sale']['purchase_date'])) {
                    return (string) $odoo['sale']['purchase_date'];
                }
            } catch (Throwable $e) {
                Log::info('Product lookup purchase-date enrichment skipped', [
                    'serial' => $serial,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return null;
    }

    /**
     * @return array{is_registered: bool, warranty_reference: string|null}
     */
    protected function resolveRegistrationStatus(string $query, Product $product): array
    {
        $candidates = array_values(array_unique(array_filter([
            trim($query),
            strtoupper(trim($query)),
        ], fn (string $value) => $value !== '')));

        // Only an exact warranty serial match means this unit is already registered.
        $warranty = Warranty::query()
            ->whereIn('serial_number', $candidates)
            ->latest('id')
            ->first(['id', 'reference', 'serial_number']);

        if ($warranty) {
            return [
                'is_registered' => true,
                'warranty_reference' => $warranty->reference,
            ];
        }

        return [
            'is_registered' => false,
            'warranty_reference' => null,
        ];
    }
}
