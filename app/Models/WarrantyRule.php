<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class WarrantyRule extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'product_id',
        'product_category_id',
        'warranty_duration_months',
        'registration_grace_days',
        'eligible_purchase_sources',
        'receipt_required',
        'serial_validation_mandatory',
        'manual_verification_allowed',
        'start_date_method',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'eligible_purchase_sources' => 'array',
            'receipt_required' => 'boolean',
            'serial_validation_mandatory' => 'boolean',
            'manual_verification_allowed' => 'boolean',
            'is_active' => 'boolean',
            'warranty_duration_months' => 'integer',
            'registration_grace_days' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }
}
