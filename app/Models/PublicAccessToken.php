<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PublicAccessToken extends Model
{
    protected $fillable = [
        'token',
        'type',
        'customer_id',
        'warranty_id',
        'expires_at',
        'used_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function warranty(): BelongsTo
    {
        return $this->belongsTo(Warranty::class);
    }

    public function isValid(): bool
    {
        return $this->used_at === null && $this->expires_at->isFuture();
    }

    public static function issue(string $type, ?Customer $customer = null, ?Warranty $warranty = null, int $days = 14, array $meta = []): self
    {
        return static::create([
            'token' => Str::random(48),
            'type' => $type,
            'customer_id' => $customer?->id,
            'warranty_id' => $warranty?->id,
            'expires_at' => now()->addDays($days),
            'meta' => $meta,
        ]);
    }
}
