<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Dealer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'dealer_code',
        'contact_person',
        'mobile_number',
        'email',
        'county',
        'town',
        'physical_location',
        'is_active',
        'is_authorised',
        'odoo_partner_id',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_authorised' => 'boolean',
        ];
    }

    public function warranties(): HasMany
    {
        return $this->hasMany(Warranty::class);
    }
}
