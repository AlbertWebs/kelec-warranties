<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OdooProductSyncRun extends Model
{
    protected $fillable = [
        'sync_type',
        'status',
        'started_by',
        'started_at',
        'completed_at',
        'total_records',
        'processed_records',
        'created_records',
        'updated_records',
        'failed_records',
        'error_message',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'total_records' => 'integer',
            'processed_records' => 'integer',
            'created_records' => 'integer',
            'updated_records' => 'integer',
            'failed_records' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function starter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    public function failures(): HasMany
    {
        return $this->hasMany(OdooProductSyncFailure::class, 'sync_run_id');
    }
}

