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
    public function __construct(protected OdooProductService $odooProductService) {}

    /**
     * @return array{success: bool, source?: string, message: string, product?: Product, purchase_date?: string|null, is_registered?: bool, warranty_reference?: string|null, odoo_unavailable?: bool}
     */
    public function lookup(string $query): array
    {
        $query = trim($query);
        if ($query === '') {
            return ['success' => false, 'message' => 'Please provide a valid search value.'];
        }

        $local = $this->findLocal($query);
        if ($local) {
            $registration = $this->resolveRegistrationStatus($query, $local);

            return [
                'success' => true,
                'source' => 'local',
                'message' => 'Product found.',
                'product' => $local,
                'purchase_date' => $this->resolvePurchaseDate($query, $local),
                'is_registered' => $registration['is_registered'],
                'warranty_reference' => $registration['warranty_reference'],
            ];
        }

        $negativeKey = 'product_lookup_negative:'.md5(strtolower($query));
        if (Cache::has($negativeKey)) {
            return [
                'success' => false,
                'message' => 'We could not find a product matching the information provided.',
                'is_registered' => false,
            ];
        }

        try {
            $odooProduct = $this->odooProductService->searchProduct($query);
            if (! $odooProduct) {
                Cache::put($negativeKey, true, now()->addMinutes(5));

                return [
                    'success' => false,
                    'message' => 'We could not find a product matching the information provided.',
                    'is_registered' => false,
                ];
            }

            $product = $this->odooProductService->upsertProductFromOdoo($odooProduct);
            $registration = $this->resolveRegistrationStatus($query, $product);

            return [
                'success' => true,
                'source' => 'odoo',
                'message' => 'Product found.',
                'product' => $product,
                'purchase_date' => $this->resolvePurchaseDate($query, $product),
                'is_registered' => $registration['is_registered'],
                'warranty_reference' => $registration['warranty_reference'],
            ];
        } catch (Throwable $e) {
            Log::warning('Product lookup Odoo fallback failed', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'odoo_unavailable' => true,
                'message' => 'We could not complete the product lookup at the moment. Please try again shortly.',
            ];
        }
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
