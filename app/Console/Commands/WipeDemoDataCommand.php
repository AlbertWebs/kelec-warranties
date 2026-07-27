<?php

namespace App\Console\Commands;

use App\Services\DemoDataService;
use Illuminate\Console\Command;

class WipeDemoDataCommand extends Command
{
    protected $signature = 'demo:wipe {--force : Skip confirmation}';

    protected $description = 'Delete transactional test data while preserving staff users, roles, settings, and core catalog';

    public function handle(DemoDataService $demoData): int
    {
        if (! $this->option('force') && ! $this->confirm('Wipe all customers, warranties, claims, and related logs?')) {
            $this->warn('Cancelled.');

            return self::SUCCESS;
        }

        $result = $demoData->wipe();
        $this->info('Wiped tables: '.implode(', ', $result['tables']));

        return self::SUCCESS;
    }
}
