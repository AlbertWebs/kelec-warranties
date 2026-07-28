<?php

namespace App\Console\Commands;

use App\Services\ProductSyncService;
use Illuminate\Console\Command;

class SyncOdooProductsCommand extends Command
{
    protected $signature = 'odoo:sync-products {--type=incremental : full|incremental} {--now : Run immediately in foreground}';

    protected $description = 'Queue or run product synchronization from Odoo';

    public function handle(ProductSyncService $productSyncService): int
    {
        $type = (string) $this->option('type');
        if (! in_array($type, ['full', 'incremental'], true)) {
            $this->error('Invalid --type. Use full or incremental.');

            return self::FAILURE;
        }

        $run = $productSyncService->queueSync($type, null);
        $this->info("Odoo product sync queued. Run ID: {$run->id}, type: {$type}");

        if ($this->option('now')) {
            $productSyncService->runSync($run->id);
            $this->info('Odoo product sync completed in foreground.');
        }

        return self::SUCCESS;
    }
}

