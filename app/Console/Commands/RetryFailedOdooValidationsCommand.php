<?php

namespace App\Console\Commands;

use App\Jobs\RetryFailedOdooValidation;
use App\Models\IntegrationFailure;
use Illuminate\Console\Command;

class RetryFailedOdooValidationsCommand extends Command
{
    protected $signature = 'odoo:retry-failed-validations';

    protected $description = 'Queue retries for failed Odoo validations';

    public function handle(): int
    {
        IntegrationFailure::query()
            ->where('status', 'pending')
            ->where(function ($q) {
                $q->whereNull('next_retry_at')->orWhere('next_retry_at', '<=', now());
            })
            ->limit(100)
            ->get()
            ->each(fn (IntegrationFailure $failure) => RetryFailedOdooValidation::dispatch($failure->id));

        $this->info('Queued Odoo validation retries.');

        return self::SUCCESS;
    }
}
