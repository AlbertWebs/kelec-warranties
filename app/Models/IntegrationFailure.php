<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntegrationFailure extends Model
{
    protected $fillable = [
        'integration',
        'action',
        'warranty_id',
        'error_message',
        'retry_count',
        'status',
        'next_retry_at',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'next_retry_at' => 'datetime',
            'retry_count' => 'integer',
        ];
    }

    public function warranty(): BelongsTo
    {
        return $this->belongsTo(Warranty::class);
    }
}
