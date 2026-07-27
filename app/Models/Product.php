<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_category_id',
        'name',
        'product_code',
        'sku',
        'model',
        'brand',
        'default_warranty_months',
        'registration_grace_days',
        'odoo_product_id',
        'is_active',
        'serial_tracking_enabled',
        'manual_verification_allowed',
        'receipt_required',
        'is_odoo_managed',
        'warranty_terms',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'serial_tracking_enabled' => 'boolean',
            'manual_verification_allowed' => 'boolean',
            'receipt_required' => 'boolean',
            'is_odoo_managed' => 'boolean',
            'default_warranty_months' => 'integer',
            'registration_grace_days' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    public function warranties(): HasMany
    {
        return $this->hasMany(Warranty::class);
    }

    public function resolvedWarrantyMonths(): int
    {
        return $this->default_warranty_months
            ?? $this->category?->default_warranty_months
            ?? 12;
    }
}
