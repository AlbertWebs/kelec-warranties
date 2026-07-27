<?php

namespace App\Enums;

enum WarrantyStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case PendingVerification = 'pending_verification';
    case UnderReview = 'under_review';
    case Active = 'active';
    case Rejected = 'rejected';
    case Expired = 'expired';
    case Cancelled = 'cancelled';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Submitted',
            self::PendingVerification => 'Pending Verification',
            self::UnderReview => 'Under Review',
            self::Active => 'Active',
            self::Rejected => 'Rejected',
            self::Expired => 'Expired',
            self::Cancelled => 'Cancelled',
            self::Suspended => 'Suspended',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Submitted => 'blue',
            self::PendingVerification => 'yellow',
            self::UnderReview => 'indigo',
            self::Active => 'green',
            self::Rejected => 'red',
            self::Expired => 'orange',
            self::Cancelled => 'gray',
            self::Suspended => 'purple',
        };
    }

    /**
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Submitted, self::PendingVerification],
            self::Submitted => [self::PendingVerification, self::Active, self::UnderReview],
            self::PendingVerification => [self::UnderReview, self::Active, self::Rejected],
            self::UnderReview => [self::Active, self::Rejected, self::PendingVerification],
            self::Active => [self::Suspended, self::Cancelled, self::Expired],
            self::Suspended => [self::Active, self::Cancelled],
            self::Rejected, self::Expired, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->allowedTransitions(), true);
    }
}
