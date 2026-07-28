<?php

namespace App\Services;

use App\Models\Product;
use App\Services\Odoo\OdooProductService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProductLookupService
{
    public function __construct(protected OdooProductService $odooProductService) {}

    /**
     * @return array{success: bool, source?: string, message: string, product?: Product, odoo_unavailable?: bool}
     */
    public function lookup(string $query): array
    {
        $query = trim($query);
        if ($query === '') {
            return ['success' => false, 'message' => 'Please provide a valid search value.'];
        }

        $local = $this->findLocal($query);
        if ($local) {
            return [
                'success' => true,
                'source' => 'local',
                'message' => 'Product found.',
                'product' => $local,
            ];
        }

        $negativeKey = 'product_lookup_negative:'.md5(strtolower($query));
        if (Cache::has($negativeKey)) {
            return [
                'success' => false,
                'message' => 'We could not find a product matching the information provided.',
            ];
        }

        try {
            $odooProduct = $this->odooProductService->searchProduct($query);
            if (! $odooProduct) {
                Cache::put($negativeKey, true, now()->addMinutes(5));

                return [
                    'success' => false,
                    'message' => 'We could not find a product matching the information provided.',
                ];
            }

            $product = $this->odooProductService->upsertProductFromOdoo($odooProduct);

            return [
                'success' => true,
                'source' => 'odoo',
                'message' => 'Product found.',
                'product' => $product,
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
}

