<?php

namespace App\Models;

use App\Enums\PurchaseSourceType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseSource extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'type',
        'description',
        'is_active',
        'requires_dealer',
        'requires_branch',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'type' => PurchaseSourceType::class,
            'is_active' => 'boolean',
            'requires_dealer' => 'boolean',
            'requires_branch' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function warranties(): HasMany
    {
        return $this->hasMany(Warranty::class);
    }
}
