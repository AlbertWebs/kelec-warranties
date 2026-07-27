<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ValidateWarrantyAgainstOdoo implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $warrantyId) {}

    public function handle(): void
    {
        // Phase 2 expands full revalidation persistence. Phase 1 keeps the job contract ready.
    }
}
