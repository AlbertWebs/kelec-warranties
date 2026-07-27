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

    public function badgeClasses(): string
    {
        return match ($this->badgeColor()) {
            'green' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
            'yellow' => 'bg-amber-50 text-amber-800 ring-amber-600/20',
            'blue' => 'bg-sky-50 text-sky-700 ring-sky-600/20',
            'indigo' => 'bg-indigo-50 text-indigo-700 ring-indigo-600/20',
            'red' => 'bg-red-50 text-red-700 ring-red-600/20',
            'orange' => 'bg-orange-50 text-orange-700 ring-orange-600/20',
            'purple' => 'bg-violet-50 text-violet-700 ring-violet-600/20',
            default => 'bg-slate-100 text-slate-600 ring-slate-500/20',
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
