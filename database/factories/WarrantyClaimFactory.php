<?php

namespace Database\Factories;

use App\Enums\ClaimStatus;
use App\Models\Customer;
use App\Models\Warranty;
use App\Models\WarrantyClaim;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WarrantyClaim>
 */
class WarrantyClaimFactory extends Factory
{
    protected $model = WarrantyClaim::class;

    public function definition(): array
    {
        return [
            'reference' => 'CLM-'.now()->format('Y').'-'.$this->faker->unique()->numerify('######'),
            'customer_id' => Customer::factory(),
            'warranty_id' => Warranty::factory(),
            'subject' => 'Product not working',
            'description' => 'The appliance stopped working after a week of use.',
            'status' => ClaimStatus::Submitted,
        ];
    }
}
