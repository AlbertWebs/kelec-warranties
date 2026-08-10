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
                'is_registered' => $result['is_registered'] ?? false,
                'can_register' => ! ($result['odoo_unavailable'] ?? false),
            ], $status);
        }

        $product = $result['product'];
        $isRegistered = (bool) ($result['is_registered'] ?? false);

        return response()->json([
            'success' => true,
            'source' => $result['source'] ?? 'local',
            'message' => $result['message'],
            'is_registered' => $isRegistered,
            'can_register' => ! $isRegistered,
            'warranty_reference' => $result['warranty_reference'] ?? null,
            'product' => [
                'id' => $product->id,
                'name' => $product->customerFacingName(),
                'model' => $product->model ?: $product->default_code ?: $product->sku,
                'category_name' => $product->category_name ?: $product->category?->name,
                'purchase_date' => $result['purchase_date'] ?? null,
                'serial_number' => $product->serial_number,
                'barcode' => $product->barcode,
            ],
        ]);
    }
}
