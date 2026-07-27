<?php

namespace App\Console\Commands;

use App\Enums\WarrantyStatus;
use App\Models\Warranty;
use App\Services\WarrantyStatusService;
use Illuminate\Console\Command;

class MarkExpiredWarranties extends Command
{
    protected $signature = 'warranties:mark-expired';

    protected $description = 'Mark active warranties as expired when past expiry date';

    public function handle(WarrantyStatusService $statusService): int
    {
        Warranty::query()
            ->where('status', WarrantyStatus::Active)
            ->whereDate('warranty_expiry_date', '<', now()->toDateString())
            ->each(function (Warranty $warranty) use ($statusService) {
                $statusService->transition($warranty, WarrantyStatus::Expired, null, 'Automatically expired by scheduler');
            });

        $this->info('Expired warranties processed.');

        return self::SUCCESS;
    }
}
