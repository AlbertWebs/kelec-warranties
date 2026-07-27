<?php

namespace App\Console\Commands;

use App\Jobs\SendWarrantyConfirmation;
use App\Models\NotificationLog;
use Illuminate\Console\Command;

class RetryFailedNotificationsCommand extends Command
{
    protected $signature = 'notifications:retry-failed';

    protected $description = 'Retry failed warranty notifications';

    public function handle(): int
    {
        NotificationLog::query()
            ->where('status', 'failed')
            ->where('retry_count', '<', 3)
            ->whereNotNull('warranty_id')
            ->latest()
            ->limit(50)
            ->get()
            ->each(function (NotificationLog $log) {
                SendWarrantyConfirmation::dispatch($log->warranty_id, $log->notification_type);
            });

        $this->info('Queued notification retries.');

        return self::SUCCESS;
    }
}
