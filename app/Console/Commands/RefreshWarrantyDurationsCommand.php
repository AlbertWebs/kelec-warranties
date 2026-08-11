<?php

namespace App\Console\Commands;

use App\Models\Warranty;
use App\Services\WarrantyDurationResolver;
use App\Services\WarrantyEligibilityService;
use Illuminate\Console\Command;

class RefreshWarrantyDurationsCommand extends Command
{
    protected $signature = 'warranties:refresh-duration {--dry-run : Show changes without saving}';

    protected $description = 'Recalculate warranty duration and expiry dates from current settings and product rules';

    public function handle(
        WarrantyDurationResolver $durationResolver,
        WarrantyEligibilityService $eligibilityService,
    ): int {
        $dryRun = (bool) $this->option('dry-run');
        $updated = 0;

        Warranty::query()
            ->with('product.category')
            ->whereNotNull('purchase_date')
            ->orderBy('id')
            ->chunkById(100, function ($warranties) use ($durationResolver, $eligibilityService, $dryRun, &$updated) {
                foreach ($warranties as $warranty) {
                    $duration = $durationResolver->forProductWithRule($warranty->product);
                    [$start, $expiry] = $eligibilityService->resolvePeriodFromPurchaseDate(
                        $warranty->purchase_date,
                        $duration
                    );

                    if (! $start || ! $expiry) {
                        continue;
                    }

                    $changes = [
                        'warranty_duration_months' => $duration,
                        'warranty_start_date' => $start->toDateString(),
                        'warranty_expiry_date' => $expiry->toDateString(),
                    ];

                    $current = [
                        'warranty_duration_months' => (int) $warranty->warranty_duration_months,
                        'warranty_start_date' => optional($warranty->warranty_start_date)->toDateString(),
                        'warranty_expiry_date' => optional($warranty->warranty_expiry_date)->toDateString(),
                    ];

                    if ($current === $changes) {
                        continue;
                    }

                    $this->line(sprintf(
                        '%s: %d months, expiry %s → %d months, expiry %s',
                        $warranty->reference,
                        $current['warranty_duration_months'],
                        $current['warranty_expiry_date'] ?? '—',
                        $changes['warranty_duration_months'],
                        $changes['warranty_expiry_date']
                    ));

                    if (! $dryRun) {
                        $warranty->update($changes);
                    }

                    $updated++;
                }
            });

        $this->info($dryRun
            ? "Dry run complete. {$updated} warranties would be updated."
            : "Updated {$updated} warranties.");

        return self::SUCCESS;
    }
}
