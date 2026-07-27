<?php

namespace App\Enums;

enum ClaimStatus: string
{
    case Submitted = 'submitted';
    case InReview = 'in_review';
    case Resolved = 'resolved';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'Submitted',
            self::InReview => 'In Review',
            self::Resolved => 'Resolved',
            self::Closed => 'Closed',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Submitted => 'blue',
            self::InReview => 'indigo',
            self::Resolved => 'green',
            self::Closed => 'gray',
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
}
