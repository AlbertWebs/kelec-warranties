<?php

namespace App\Enums;

enum PurchaseSourceType: string
{
    case BrandShop = 'brand_shop';
    case Dealer = 'dealer';
    case Jumia = 'jumia';
    case Kilimall = 'kilimall';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::BrandShop => 'Brand Shop',
            self::Dealer => 'Dealer',
            self::Jumia => 'Jumia',
            self::Kilimall => 'Kilimall',
            self::Other => 'Other authorised seller',
        };
    }
}
