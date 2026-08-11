<?php

namespace App\Services;

use App\Enums\WarrantyStatus;
use App\Jobs\SyncWarrantyToOdoo;
use App\Models\User;
use App\Models\Warranty;
use Illuminate\Support\Carbon;

class WarrantyAdminService
{
    public function __construct(
        protected WarrantyStatusService $statusService,
        protected WarrantyEligibilityService $eligibilityService,
        protected WarrantyDurationResolver $durationResolver,
        protected NotificationDispatcher $notificationDispatcher,
        protected AuditLogger $auditLogger,
    ) {}

    public function approve(Warranty $warranty, User $admin, ?Carbon $startDate = null): Warranty
    {
        $warranty->loadMissing('product.category');
        $duration = $this->durationResolver->forProductWithRule($warranty->product);

        $purchaseStart = $startDate
            ?? ($warranty->purchase_date ? Carbon::parse($warranty->purchase_date)->startOfDay() : null);

        if (! $purchaseStart) {
            throw new \InvalidArgumentException('A purchase date is required before a warranty can be activated.');
        }

        [$start, $expiry] = $this->eligibilityService
            ->resolvePeriodFromPurchaseDate($purchaseStart, $duration);

        $warranty->update([
            'warranty_start_date' => $start,
            'warranty_expiry_date' => $expiry,
            'warranty_duration_months' => $duration,
            'approved_by' => $admin->id,
            'approved_at' => now(),
            'requires_manual_verification' => false,
            'rejection_reason' => null,
        ]);

        $this->statusService->transition($warranty, WarrantyStatus::Active, $admin, 'Approved by administrator');
        $this->auditLogger->log('warranty_approved', $warranty);
        $this->notificationDispatcher->sendWarrantyNotification($warranty->fresh(['customer', 'product']), 'warranty_activated');
        SyncWarrantyToOdoo::dispatch($warranty->id);

        return $warranty->fresh(['customer', 'product', 'approver']);
    }

    public function reject(Warranty $warranty, User $admin, string $reason): Warranty
    {
        $warranty->update([
            'rejection_reason' => $reason,
            'approved_by' => null,
            'approved_at' => null,
        ]);

        $this->statusService->transition($warranty, WarrantyStatus::Rejected, $admin, $reason);
        $this->auditLogger->log('warranty_rejected', $warranty, null, ['reason' => $reason]);
        $this->notificationDispatcher->sendWarrantyNotification($warranty->fresh(['customer', 'product']), 'warranty_rejected');

        return $warranty->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Warranty $warranty, array $data, User $admin): Warranty
    {
        $warranty->loadMissing('product.category');
        $previous = $warranty->only(array_keys($data));

        $shouldRecalculatePeriod = array_key_exists('warranty_duration_months', $data)
            || array_key_exists('warranty_start_date', $data)
            || array_key_exists('purchase_date', $data);

        if ($shouldRecalculatePeriod) {
            $duration = (int) (
                $data['warranty_duration_months']
                ?? $warranty->warranty_duration_months
                ?? $this->durationResolver->forProductWithRule($warranty->product)
            );
            $data['warranty_duration_months'] = $duration;

            $startInput = $data['warranty_start_date']
                ?? $data['purchase_date']
                ?? $warranty->warranty_start_date
                ?? $warranty->purchase_date;

            if ($startInput) {
                [$start, $expiry] = $this->eligibilityService->resolvePeriodFromPurchaseDate(
                    Carbon::parse($startInput),
                    $duration
                );

                $data['warranty_start_date'] = $start?->toDateString();
                $data['warranty_expiry_date'] = $expiry?->toDateString();
            }
        }

        $warranty->update($data);
        $this->auditLogger->log('warranty_updated', $warranty, $previous, $warranty->only(array_keys($data)));

        return $warranty->fresh();
    }
}
