<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\WarrantyLookupRequest;
use App\Services\WarrantyQueryService;
use Illuminate\Http\JsonResponse;

class WarrantyLookupController extends Controller
{
    public function __construct(protected WarrantyQueryService $warrantyQueryService) {}

    public function lookup(WarrantyLookupRequest $request): JsonResponse
    {
        $warranty = $this->warrantyQueryService->lookup(
            $request->validated('reference'),
            $request->validated('serial_number'),
            $request->validated('mobile_number'),
        );

        if (! $warranty) {
            return response()->json([
                'success' => false,
                'message' => 'No warranty matched the details provided. Please check and try again.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Warranty found.',
            'warranty' => [
                'reference' => $warranty->reference,
                'status' => $warranty->status->label(),
                'customer_name' => $warranty->customer->maskedName(),
                'mobile' => $warranty->customer->maskedMobile(),
                'product' => $warranty->displayProductName(),
                'model' => $warranty->displayModel() ?? '—',
                'serial_number' => $warranty->serial_number,
                'purchase_source' => $warranty->purchaseSource?->name ?? $warranty->branch_name ?? '—',
                'warranty_start_date' => optional($warranty->warranty_start_date)?->format('d M Y') ?? '—',
                'warranty_expiry_date' => optional($warranty->warranty_expiry_date)?->format('d M Y') ?? '—',
                'remaining_days' => $warranty->remainingDays(),
                'certificate_url' => route('warranty.certificate', $warranty->reference),
                'certificate_pdf_url' => route('warranty.certificate.download', $warranty->reference),
            ],
        ]);
    }
}

