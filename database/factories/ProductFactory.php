<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'product_category_id' => ProductCategory::factory(),
            'name' => 'K-Elec '.$this->faker->unique()->word(),
            'sku' => strtoupper($this->faker->unique()->bothify('KE-####')),
            'default_code' => strtoupper($this->faker->unique()->bothify('SKU-####')),
            'barcode' => $this->faker->unique()->numerify('###########'),
            'display_name' => 'K-Elec '.$this->faker->words(2, true),
            'model' => 'KE-'.$this->faker->numberBetween(1000, 9999),
            'brand' => 'K-Elec',
            'brand_name' => 'K-Elec',
            'category_name' => 'General',
            'default_warranty_months' => 12,
            'registration_grace_days' => 30,
            'is_active' => true,
            'active' => true,
            'sale_ok' => true,
            'purchase_ok' => false,
            'serial_tracking_enabled' => true,
            'manual_verification_allowed' => true,
            'receipt_required' => false,
            'is_odoo_managed' => false,
            'sync_status' => 'synced',
            'last_synced_at' => now(),
        ];
    }
}
