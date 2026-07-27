<?php

namespace App\Jobs;

use App\Services\PosWarrantyImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class ImportPosWarranty implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(public array $payload) {}

    public function handle(PosWarrantyImportService $importService): void
    {
        try {
            $importService->import($this->payload);
        } catch (Throwable $e) {
            Log::error('POS warranty import failed', [
                'error' => $e->getMessage(),
                'pos_order_id' => $this->payload['odoo_pos_order_id'] ?? $this->payload['pos_order_id'] ?? null,
                'serial_number' => $this->payload['serial_number'] ?? null,
            ]);

            throw $e;
        }
    }
}
