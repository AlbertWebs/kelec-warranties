<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OdooProductSyncFailure extends Model
{
    protected $fillable = [
        'sync_run_id',
        'external_id',
        'identifier',
        'error_message',
        'retry_count',
        'status',
        'payload',
        'last_attempt_at',
    ];

    protected function casts(): array
    {
        return [
            'retry_count' => 'integer',
            'payload' => 'array',
            'last_attempt_at' => 'datetime',
        ];
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(OdooProductSyncRun::class, 'sync_run_id');
    }
}

