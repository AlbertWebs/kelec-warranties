<?php

namespace App\Models;

use App\Services\WarrantyDurationResolver;
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
        'display_name',
        'product_code',
        'sku',
        'default_code',
        'barcode',
        'serial_number',
        'model',
        'brand',
        'odoo_id',
        'odoo_product_id',
        'product_template_id',
        'product_type',
        'category_id',
        'category_name',
        'brand_id',
        'brand_name',
        'description',
        'description_sale',
        'list_price',
        'standard_price',
        'currency',
        'uom_id',
        'uom_name',
        'active',
        'sale_ok',
        'purchase_ok',
        'tracking',
        'image_url',
        'odoo_created_at',
        'odoo_updated_at',
        'last_synced_at',
        'sync_status',
        'raw_odoo_data',
        'default_warranty_months',
        'registration_grace_days',
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
            'active' => 'boolean',
            'sale_ok' => 'boolean',
            'purchase_ok' => 'boolean',
            'default_warranty_months' => 'integer',
            'registration_grace_days' => 'integer',
            'list_price' => 'decimal:2',
            'standard_price' => 'decimal:2',
            'odoo_created_at' => 'datetime',
            'odoo_updated_at' => 'datetime',
            'last_synced_at' => 'datetime',
            'raw_odoo_data' => 'array',
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
        return app(WarrantyDurationResolver::class)->forProduct($this);
    }

    public function customerFacingName(): string
    {
        return $this->display_name ?: $this->name;
    }
}
