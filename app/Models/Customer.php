<?php

namespace App\Models;

use App\Enums\WarrantyStatus;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Customer extends Authenticatable
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'full_name',
        'mobile_number',
        'mobile_normalized',
        'email',
        'password',
        'county',
        'town',
        'odoo_customer_id',
        'marketing_consent',
        'marketing_consent_at',
        'possible_duplicate',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'marketing_consent' => 'boolean',
            'marketing_consent_at' => 'datetime',
            'possible_duplicate' => 'boolean',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function warranties(): HasMany
    {
        return $this->hasMany(Warranty::class);
    }

    public function claims(): HasMany
    {
        return $this->hasMany(WarrantyClaim::class);
    }

    public function consents(): HasMany
    {
        return $this->hasMany(WarrantyConsent::class);
    }

    public function notificationLogs(): HasMany
    {
        return $this->hasMany(NotificationLog::class);
    }

    public function activeWarrantiesCount(): int
    {
        return $this->warranties()->where('status', WarrantyStatus::Active)->count();
    }

    public function claimableWarranties(): HasMany
    {
        return $this->warranties()->where('status', WarrantyStatus::Active);
    }

    public function hasPortalAccount(): bool
    {
        return filled($this->password);
    }

    public function maskedMobile(): string
    {
        $mobile = $this->mobile_normalized ?: $this->mobile_number;
        if (strlen($mobile) < 6) {
            return '****';
        }

        return substr($mobile, 0, 3).str_repeat('*', max(strlen($mobile) - 6, 0)).substr($mobile, -3);
    }

    public function maskedEmail(): ?string
    {
        if (! $this->email || ! str_contains($this->email, '@')) {
            return $this->email;
        }

        [$local, $domain] = explode('@', $this->email, 2);

        return substr($local, 0, 1).'***@'.$domain;
    }

    public function maskedName(): string
    {
        $parts = preg_split('/\s+/', trim($this->full_name)) ?: [];
        if (count($parts) === 0) {
            return 'Customer';
        }

        $first = $parts[0];
        $last = count($parts) > 1 ? substr(end($parts), 0, 1).'.' : '';

        return trim($first.' '.$last);
    }
}
