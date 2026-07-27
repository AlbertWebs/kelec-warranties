<?php

namespace App\Jobs;

use App\Models\Warranty;
use App\Services\Odoo\OdooWarrantyService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncWarrantyToOdoo implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $warrantyId) {}

    public function handle(OdooWarrantyService $warrantyService): void
    {
        $warranty = Warranty::find($this->warrantyId);
        if (! $warranty) {
            return;
        }

        $warrantyService->syncWarranty($warranty);
    }
}
