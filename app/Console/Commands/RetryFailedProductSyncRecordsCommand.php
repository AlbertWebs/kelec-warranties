<?php

namespace App\Console\Commands;

use App\Models\OdooProductSyncFailure;
use App\Services\ProductSyncService;
use Illuminate\Console\Command;

class RetryFailedProductSyncRecordsCommand extends Command
{
    protected $signature = 'odoo:retry-failed-product-sync {--limit=100}';

    protected $description = 'Retry failed Odoo product synchronization records';

    public function handle(ProductSyncService $productSyncService): int
    {
        $failures = OdooProductSyncFailure::query()
            ->where('status', 'pending')
            ->limit((int) $this->option('limit'))
            ->get();

        foreach ($failures as $failure) {
            $productSyncService->retryFailure($failure);
        }

        $this->info('Retried '.$failures->count().' failed product sync record(s).');

        return self::SUCCESS;
    }
}

