<?php

namespace App\Console\Commands;

use App\Services\DemoDataService;
use Illuminate\Console\Command;

class SeedDemoDataCommand extends Command
{
    protected $signature = 'demo:seed';

    protected $description = 'Seed demo customers, warranties, claims, and logs for admin UI review';

    public function handle(DemoDataService $demoData): int
    {
        try {
            $counts = $demoData->seed();
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Demo data ready: %d customers, %d warranties, %d claims.',
            $counts['customers'],
            $counts['warranties'],
            $counts['claims']
        ));

        return self::SUCCESS;
    }
}
