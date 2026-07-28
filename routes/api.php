<?php

use App\Http\Controllers\Api\OdooPosSaleController;
use App\Http\Controllers\Api\ProductLookupController;
use App\Http\Controllers\Api\WarrantyLookupController;
use Illuminate\Support\Facades\Route;

Route::middleware(['throttle:60,1', 'integration'])->group(function () {
    Route::post('/odoo/pos-sale', OdooPosSaleController::class)->name('api.odoo.pos-sale');
});

Route::middleware('throttle:60,1')->group(function () {
    Route::get('/warranties/{reference}/status', function (string $reference) {
        $warranty = \App\Models\Warranty::where('reference', strtoupper($reference))->first();

        if (! $warranty) {
            return response()->json(['message' => 'Warranty not found.'], 404);
        }

        return response()->json([
            'reference' => $warranty->reference,
            'status' => $warranty->status instanceof \App\Enums\WarrantyStatus
                ? $warranty->status->value
                : $warranty->status,
            'serial_number' => $warranty->serial_number,
            'expiry_date' => optional($warranty->warranty_expiry_date)?->toDateString(),
        ]);
    })->name('api.warranties.status');
});

Route::post('/products/lookup', [ProductLookupController::class, 'lookup'])
    ->middleware('throttle:product-lookup-api')
    ->name('api.products.lookup');

Route::post('/warranties/lookup', [WarrantyLookupController::class, 'lookup'])
    ->middleware('throttle:warranty-lookup')
    ->name('api.warranties.lookup');
