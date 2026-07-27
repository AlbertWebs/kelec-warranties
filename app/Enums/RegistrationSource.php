<?php

namespace App\Enums;

enum RegistrationSource: string
{
    case PublicPortal = 'public_portal';
    case OdooPos = 'odoo_pos';
    case Admin = 'admin';
    case CustomerCompletion = 'customer_completion';

    public function label(): string
    {
        return match ($this) {
            self::PublicPortal => 'Public Portal',
            self::OdooPos => 'Odoo POS',
            self::Admin => 'Administrator',
            self::CustomerCompletion => 'Customer Completion',
        };
    }
}
