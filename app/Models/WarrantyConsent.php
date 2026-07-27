<?php

namespace App\Models;

use App\Enums\ConsentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarrantyConsent extends Model
{
    protected $fillable = [
        'warranty_id',
        'customer_id',
        'consent_type',
        'granted',
        'source',
        'ip_address',
        'user_agent',
        'consented_at',
    ];

    protected function casts(): array
    {
        return [
            'consent_type' => ConsentType::class,
            'granted' => 'boolean',
            'consented_at' => 'datetime',
        ];
    }

    public function warranty(): BelongsTo
    {
        return $this->belongsTo(Warranty::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
