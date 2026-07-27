<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ImportPosWarranty;
use App\Services\PosWarrantyImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OdooPosSaleController extends Controller
{
    public function __invoke(Request $request, PosWarrantyImportService $importService): JsonResponse
    {
        $data = $request->validate([
            'serial_number' => ['required', 'string', 'max:100'],
            'branch_name' => ['required', 'string', 'max:100'],
            'odoo_pos_order_id' => ['nullable', 'string', 'max:100'],
            'pos_order_id' => ['nullable', 'string', 'max:100'],
            'full_name' => ['nullable', 'string', 'max:150'],
            'customer_name' => ['nullable', 'string', 'max:150'],
            'mobile_number' => ['nullable', 'string', 'max:20'],
            'customer_mobile' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:150'],
            'product_id' => ['nullable', 'integer'],
            'odoo_product_id' => ['nullable', 'string', 'max:100'],
            'odoo_customer_id' => ['nullable', 'string', 'max:100'],
            'odoo_serial_id' => ['nullable', 'string', 'max:100'],
            'product_name' => ['nullable', 'string', 'max:150'],
            'product_model' => ['nullable', 'string', 'max:150'],
            'sku' => ['nullable', 'string', 'max:100'],
            'purchase_date' => ['nullable', 'date'],
            'invoice_number' => ['nullable', 'string', 'max:100'],
            'marketing_consent' => ['sometimes', 'boolean'],
            'async' => ['sometimes', 'boolean'],
        ]);

        if ($request->boolean('async')) {
            ImportPosWarranty::dispatch($data);

            return response()->json([
                'message' => 'POS warranty import queued.',
                'queued' => true,
            ], 202);
        }

        try {
            $warranty = $importService->import($data);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'POS warranty processed.',
            'reference' => $warranty->reference,
            'status' => $warranty->status->value,
            'provisional' => $warranty->requires_manual_verification,
            'expiry_date' => optional($warranty->warranty_expiry_date)?->toDateString(),
        ]);
    }
}
