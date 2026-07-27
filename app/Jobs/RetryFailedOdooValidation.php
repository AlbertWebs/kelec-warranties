<?php

namespace App\Jobs;

use App\Models\IntegrationFailure;
use App\Services\Odoo\OdooProductService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class RetryFailedOdooValidation implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $failureId) {}

    public function handle(OdooProductService $odooProductService): void
    {
        $failure = IntegrationFailure::find($this->failureId);
        if (! $failure || $failure->status === 'resolved') {
            return;
        }

        $serial = $failure->payload['serial_number'] ?? null;
        if (! $serial) {
            $failure->update(['status' => 'failed', 'error_message' => 'Missing serial number in payload']);

            return;
        }

        try {
            $result = $odooProductService->lookupBySerial($serial);
            $failure->update([
                'status' => ($result['found'] ?? false) ? 'resolved' : 'pending',
                'retry_count' => $failure->retry_count + 1,
                'error_message' => ($result['found'] ?? false) ? null : ($result['message'] ?? 'Not found'),
                'next_retry_at' => ($result['found'] ?? false) ? null : now()->addHour(),
            ]);
        } catch (Throwable $e) {
            $failure->update([
                'status' => 'pending',
                'retry_count' => $failure->retry_count + 1,
                'error_message' => $e->getMessage(),
                'next_retry_at' => now()->addHour(),
            ]);
        }
    }
}
