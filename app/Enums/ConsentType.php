<?php

namespace App\Enums;

enum ConsentType: string
{
    case Privacy = 'privacy';
    case Marketing = 'marketing';
    case WarrantyTerms = 'warranty_terms';

    public function label(): string
    {
        return match ($this) {
            self::Privacy => 'Privacy Policy',
            self::Marketing => 'Marketing Communication',
            self::WarrantyTerms => 'Warranty Terms',
        };
    }
}
