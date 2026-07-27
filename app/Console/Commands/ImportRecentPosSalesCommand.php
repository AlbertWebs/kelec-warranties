<?php

namespace App\Console\Commands;

use App\Jobs\ImportPosWarranty;
use App\Services\Odoo\OdooSalesService;
use Illuminate\Console\Command;

class ImportRecentPosSalesCommand extends Command
{
    protected $signature = 'odoo:import-pos-sales {--limit=20}';

    protected $description = 'Import recent Brand Shop POS sales into warranties';

    public function handle(OdooSalesService $salesService): int
    {
        $sales = $salesService->recentBrandShopSales((int) $this->option('limit'));

        foreach ($sales as $sale) {
            ImportPosWarranty::dispatch($sale);
        }

        $this->info('Queued '.count($sales).' POS sale(s) for warranty import.');

        return self::SUCCESS;
    }
}
