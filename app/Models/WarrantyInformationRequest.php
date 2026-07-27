<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarrantyInformationRequest extends Model
{
    protected $fillable = [
        'warranty_id',
        'requested_by',
        'token',
        'requested_fields',
        'message',
        'customer_response',
        'expires_at',
        'responded_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'requested_fields' => 'array',
            'expires_at' => 'datetime',
            'responded_at' => 'datetime',
        ];
    }

    public function warranty(): BelongsTo
    {
        return $this->belongsTo(Warranty::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
