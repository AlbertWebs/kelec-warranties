<?php

namespace App\Models;

use App\Enums\WarrantyStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarrantyStatusHistory extends Model
{
    protected $fillable = [
        'warranty_id',
        'from_status',
        'to_status',
        'changed_by',
        'reason',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'from_status' => WarrantyStatus::class,
            'to_status' => WarrantyStatus::class,
            'meta' => 'array',
        ];
    }

    public function warranty(): BelongsTo
    {
        return $this->belongsTo(Warranty::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
