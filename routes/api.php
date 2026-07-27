<?php

use App\Http\Controllers\Api\OdooPosSaleController;
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
