<?php

namespace Database\Factories;

use App\Enums\PurchaseSourceType;
use App\Models\PurchaseSource;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PurchaseSource>
 */
class PurchaseSourceFactory extends Factory
{
    protected $model = PurchaseSource::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->company();

        return [
            'name' => $name,
            'code' => Str::slug($name),
            'type' => PurchaseSourceType::Other,
            'is_active' => true,
            'sort_order' => 1,
        ];
    }
}
