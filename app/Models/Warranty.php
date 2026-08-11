<?php

namespace App\Models;

use App\Enums\RegistrationSource;
use App\Enums\WarrantyStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Warranty extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'reference',
        'customer_id',
        'product_id',
        'product_category_id',
        'purchase_source_id',
        'dealer_id',
        'product_name',
        'product_model',
        'serial_number',
        'branch_name',
        'purchase_date',
        'registration_date',
        'warranty_start_date',
        'warranty_expiry_date',
        'warranty_duration_months',
        'status',
        'eligibility_result',
        'odoo_customer_id',
        'odoo_product_id',
        'odoo_serial_id',
        'odoo_pos_order_id',
        'odoo_sales_order_id',
        'odoo_invoice_id',
        'invoice_number',
        'receipt_path',
        'receipt_original_name',
        'registration_source',
        'marketing_consent',
        'privacy_accepted',
        'consent_timestamp',
        'consent_source',
        'consent_ip',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'internal_notes',
        'customer_notes',
        'odoo_validated',
        'odoo_validation_message',
        'requires_manual_verification',
    ];

    protected function casts(): array
    {
        return [
            'status' => WarrantyStatus::class,
            'registration_source' => RegistrationSource::class,
            'purchase_date' => 'date',
            'registration_date' => 'datetime',
            'warranty_start_date' => 'date',
            'warranty_expiry_date' => 'date',
            'consent_timestamp' => 'datetime',
            'approved_at' => 'datetime',
            'marketing_consent' => 'boolean',
            'privacy_accepted' => 'boolean',
            'odoo_validated' => 'boolean',
            'requires_manual_verification' => 'boolean',
            'warranty_duration_months' => 'integer',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    public function purchaseSource(): BelongsTo
    {
        return $this->belongsTo(PurchaseSource::class);
    }

    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Dealer::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(WarrantyStatusHistory::class)->latest();
    }

    public function documents(): HasMany
    {
        return $this->hasMany(WarrantyDocument::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(WarrantyNote::class)->latest();
    }

    public function consents(): HasMany
    {
        return $this->hasMany(WarrantyConsent::class);
    }

    public function informationRequests(): HasMany
    {
        return $this->hasMany(WarrantyInformationRequest::class)->latest();
    }

    public function notificationLogs(): HasMany
    {
        return $this->hasMany(NotificationLog::class)->latest();
    }

    /**
     * Approximate how many times the customer was notified (email/SMS pair counts as one).
     */
    public function timesNotified(): int
    {
        $email = (int) ($this->email_notifications_count ?? 0);
        $sms = (int) ($this->sms_notifications_count ?? 0);

        if ($email || $sms) {
            return max($email, $sms);
        }

        return (int) ($this->notifications_sent_count ?? 0);
    }

    public function claims(): HasMany
    {
        return $this->hasMany(WarrantyClaim::class)->latest();
    }

    public function remainingDays(): ?int
    {
        if (! $this->warranty_expiry_date) {
            return null;
        }

        return (int) Carbon::today()->diffInDays($this->warranty_expiry_date, false);
    }

    public function isActive(): bool
    {
        return $this->status === WarrantyStatus::Active;
    }

    public function displayProductName(): string
    {
        if (filled($this->product_name)) {
            return $this->product_name;
        }

        return $this->product?->customerFacingName()
            ?? $this->product?->name
            ?? 'Unknown product';
    }

    public function displayModel(): ?string
    {
        if (filled($this->product_model)) {
            return $this->product_model;
        }

        return $this->product?->model
            ?? $this->product?->default_code
            ?? $this->product?->sku;
    }
}
