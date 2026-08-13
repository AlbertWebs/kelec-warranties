<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class WarrantyClaimPhoto extends Model
{
    protected $fillable = [
        'warranty_claim_id',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size',
    ];

    public function claim(): BelongsTo
    {
        return $this->belongsTo(WarrantyClaim::class, 'warranty_claim_id');
    }

    public function existsOnDisk(): bool
    {
        return Storage::disk($this->disk)->exists($this->path);
    }
}
