<?php

namespace App\Jobs;

use App\Services\ProductSyncService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncOdooProducts implements ShouldQueue
{
    use Queueable;

    public int $timeout = 3600;

    public function __construct(public int $runId) {}

    public function handle(ProductSyncService $productSyncService): void
    {
        $productSyncService->runSync($this->runId);
    }
}

