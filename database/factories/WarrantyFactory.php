<?php

namespace Database\Factories;

use App\Enums\RegistrationSource;
use App\Enums\WarrantyStatus;
use App\Models\Customer;
use App\Models\Product;
use App\Models\PurchaseSource;
use App\Models\Warranty;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Warranty>
 */
class WarrantyFactory extends Factory
{
    protected $model = Warranty::class;

    public function definition(): array
    {
        $start = now()->subDays(10);

        return [
            'reference' => 'KEL-WTY-'.now()->format('Y').'-'.$this->faker->unique()->numerify('######'),
            'customer_id' => Customer::factory(),
            'product_id' => Product::factory(),
            'purchase_source_id' => PurchaseSource::query()->first()?->id,
            'product_name' => 'Sample Product',
            'product_model' => 'KE-1000',
            'serial_number' => strtoupper($this->faker->unique()->bothify('SN########')),
            'purchase_date' => $start->toDateString(),
            'registration_date' => now(),
            'warranty_start_date' => $start->toDateString(),
            'warranty_expiry_date' => $start->copy()->addYear()->toDateString(),
            'warranty_duration_months' => 12,
            'status' => WarrantyStatus::Active,
            'registration_source' => RegistrationSource::PublicPortal,
            'marketing_consent' => false,
            'privacy_accepted' => true,
            'consent_timestamp' => now(),
            'consent_source' => 'public_portal',
            'odoo_validated' => true,
            'requires_manual_verification' => false,
        ];
    }
}
