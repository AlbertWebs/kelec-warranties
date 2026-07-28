<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ProductLookupRequest;
use App\Services\ProductLookupService;
use Illuminate\Http\JsonResponse;

class ProductLookupController extends Controller
{
    public function lookup(ProductLookupRequest $request, ProductLookupService $productLookupService): JsonResponse
    {
        $result = $productLookupService->lookup($request->validated('query'));

        if (! $result['success']) {
            $status = ($result['odoo_unavailable'] ?? false) ? 503 : 404;

            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], $status);
        }

        $product = $result['product'];

        return response()->json([
            'success' => true,
            'source' => $result['source'] ?? 'local',
            'message' => $result['message'],
            'product' => [
                'id' => $product->id,
                'odoo_id' => $product->odoo_id,
                'name' => $product->customerFacingName(),
                'default_code' => $product->default_code,
                'barcode' => $product->barcode,
                'serial_number' => $product->serial_number,
                'brand_name' => $product->brand_name ?: $product->brand,
                'category_name' => $product->category_name ?: $product->category?->name,
                'tracking' => $product->tracking,
                'last_synced_at' => optional($product->last_synced_at)?->toIso8601String(),
            ],
        ]);
    }
}

