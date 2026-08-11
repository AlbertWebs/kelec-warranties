<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\WarrantyRule;
use App\Services\SettingsService;
use App\Services\WarrantyDurationResolver;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarrantyDurationResolverTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_settings_default_is_used_when_rule_and_category_do_not_override(): void
    {
        app(SettingsService::class)->set('default_warranty_months', 36, 'warranty', 'integer');

        WarrantyRule::query()
            ->whereNull('product_id')
            ->whereNull('product_category_id')
            ->update(['warranty_duration_months' => null]);

        ProductCategory::query()->update(['default_warranty_months' => null]);

        $product = Product::factory()->create([
            'default_warranty_months' => null,
            'product_category_id' => ProductCategory::factory()->create([
                'default_warranty_months' => null,
            ])->id,
        ]);

        $this->assertSame(36, app(WarrantyDurationResolver::class)->forProductWithRule($product));
    }

    public function test_product_override_still_wins_over_settings(): void
    {
        app(SettingsService::class)->set('default_warranty_months', 36, 'warranty', 'integer');

        $product = Product::factory()->create([
            'default_warranty_months' => 24,
        ]);

        $this->assertSame(24, app(WarrantyDurationResolver::class)->forProductWithRule($product));
    }
}
