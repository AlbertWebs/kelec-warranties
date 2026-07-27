<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        $mobile = '07'.$this->faker->numerify('########');

        return [
            'full_name' => $this->faker->name(),
            'mobile_number' => $mobile,
            'mobile_normalized' => '254'.substr($mobile, 1),
            'email' => $this->faker->optional()->safeEmail(),
            'county' => 'Nairobi',
            'town' => 'Nairobi',
            'marketing_consent' => false,
        ];
    }
}
