<?php

namespace Database\Seeders;

use App\Services\DemoDataService;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $counts = app(DemoDataService::class)->seed();

        $this->command?->info(sprintf(
            'Demo data seeded (%d customers, %d warranties, %d claims).',
            $counts['customers'],
            $counts['warranties'],
            $counts['claims']
        ));
    }
}
