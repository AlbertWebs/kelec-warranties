<?php

namespace App\Jobs;

use App\Models\Warranty;
use App\Services\NotificationDispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendWarrantyConfirmation implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $warrantyId,
        public string $type = 'warranty_activated',
    ) {}

    public function handle(NotificationDispatcher $dispatcher): void
    {
        $warranty = Warranty::with(['customer', 'product'])->find($this->warrantyId);
        if (! $warranty) {
            return;
        }

        $dispatcher->sendNow($warranty, $this->type);
    }
}
