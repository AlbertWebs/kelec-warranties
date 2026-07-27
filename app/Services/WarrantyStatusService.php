<?php

namespace App\Services;

use App\Enums\WarrantyStatus;
use App\Models\User;
use App\Models\Warranty;
use App\Models\WarrantyStatusHistory;
use InvalidArgumentException;

class WarrantyStatusService
{
    public function __construct(protected AuditLogger $auditLogger) {}

    public function transition(Warranty $warranty, WarrantyStatus $to, ?User $user = null, ?string $reason = null, array $meta = []): Warranty
    {
        $from = $warranty->status instanceof WarrantyStatus ? $warranty->status : WarrantyStatus::from($warranty->status);

        if ($from === $to) {
            return $warranty;
        }

        if ($from !== WarrantyStatus::Submitted && ! $from->canTransitionTo($to) && $from !== WarrantyStatus::Draft) {
            // Allow Submitted -> anything in early workflow and pending verification transitions.
            if (! in_array($to, [WarrantyStatus::PendingVerification, WarrantyStatus::UnderReview, WarrantyStatus::Active, WarrantyStatus::Rejected], true)) {
                throw new InvalidArgumentException("Cannot transition warranty from {$from->value} to {$to->value}.");
            }
        }

        if ($to === WarrantyStatus::Rejected && blank($reason)) {
            throw new InvalidArgumentException('A rejection reason is required.');
        }

        $warranty->update([
            'status' => $to,
            'rejection_reason' => $to === WarrantyStatus::Rejected ? $reason : $warranty->rejection_reason,
        ]);

        WarrantyStatusHistory::create([
            'warranty_id' => $warranty->id,
            'from_status' => $from,
            'to_status' => $to,
            'changed_by' => $user?->id,
            'reason' => $reason,
            'meta' => $meta,
        ]);

        $this->auditLogger->log('warranty_status_changed', $warranty, ['status' => $from->value], ['status' => $to->value, 'reason' => $reason]);

        return $warranty->fresh();
    }
}
