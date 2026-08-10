<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Services\Odoo\OdooProductService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ProductLookupApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_lookup_returns_local_product_first(): void
    {
        $mock = Mockery::mock(OdooProductService::class);
        $mock->shouldReceive('lookupBySerial')->andReturn([
            'found' => true,
            'sale' => ['purchase_date' => '2026-07-15'],
        ]);
        app()->instance(OdooProductService::class, $mock);

        $product = Product::factory()->create([
            'serial_number' => 'SERIAL-LOCAL-001',
            'default_code' => 'SKU-LOCAL-001',
            'model' => 'KE-LOCAL-001',
            'category_name' => 'Cookers',
            'barcode' => '123456700001',
            'odoo_id' => '240',
        ]);

        $this->postJson('/api/products/lookup', ['query' => 'SERIAL-LOCAL-001'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('source', 'local')
            ->assertJsonPath('product.id', $product->id)
            ->assertJsonPath('product.name', $product->customerFacingName())
            ->assertJsonPath('product.model', 'KE-LOCAL-001')
            ->assertJsonPath('product.category_name', 'Cookers')
            ->assertJsonPath('product.purchase_date', '2026-07-15')
            ->assertJsonPath('is_registered', false)
            ->assertJsonPath('can_register', true);
    }

    public function test_lookup_marks_registered_serial_and_hides_register_cta_flag(): void
    {
        $mock = Mockery::mock(OdooProductService::class);
        $mock->shouldReceive('lookupBySerial')->andReturn(['found' => false, 'message' => 'not found']);
        app()->instance(OdooProductService::class, $mock);

        Product::factory()->create([
            'serial_number' => 'SERIAL-REG-001',
            'default_code' => 'SKU-REG-001',
            'model' => 'KE-REG-001',
            'category_name' => 'Cookers',
        ]);

        $warranty = \App\Models\Warranty::factory()->create([
            'serial_number' => 'SERIAL-REG-001',
        ]);

        $this->postJson('/api/products/lookup', ['query' => 'SERIAL-REG-001'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('is_registered', true)
            ->assertJsonPath('can_register', false)
            ->assertJsonPath('warranty_reference', $warranty->reference);
    }

    public function test_lookup_allows_register_when_product_not_found(): void
    {
        $mock = Mockery::mock(OdooProductService::class);
        $mock->shouldReceive('searchProduct')->once()->andReturn(null);
        app()->instance(OdooProductService::class, $mock);

        $this->postJson('/api/products/lookup', ['query' => 'MISSING-PRODUCT-001'])
            ->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('can_register', true);
    }

    public function test_lookup_falls_back_to_odoo_and_caches_locally(): void
    {
        $mock = Mockery::mock(OdooProductService::class);
        $mock->shouldReceive('searchProduct')->once()->andReturn([
            'id' => 9001,
            'name' => 'K-Elec Odoo Product',
            'display_name' => 'K-Elec Odoo Product',
            'default_code' => 'SKU-ODOO-001',
            'barcode' => '987654321000',
            'type' => 'product',
            'active' => true,
            'sale_ok' => true,
            'purchase_ok' => false,
            'tracking' => 'serial',
            'categ_id' => [10, 'Cookers'],
            'currency_id' => [1, 'KES'],
            'uom_id' => [1, 'Units'],
            'create_date' => now()->subDay()->toDateTimeString(),
            'write_date' => now()->toDateTimeString(),
        ]);
        $mock->shouldReceive('upsertProductFromOdoo')->once()->andReturnUsing(function () {
            return Product::factory()->create([
                'odoo_id' => '9001',
                'name' => 'K-Elec Odoo Product',
                'default_code' => 'SKU-ODOO-001',
                'model' => 'SKU-ODOO-001',
                'barcode' => '987654321000',
                'serial_number' => '987654321000',
                'category_name' => 'Cookers',
                'tracking' => 'serial',
                'last_synced_at' => now(),
            ]);
        });
        $mock->shouldReceive('lookupBySerial')->andReturn(['found' => false, 'message' => 'not found']);
        app()->instance(OdooProductService::class, $mock);

        $this->postJson('/api/products/lookup', ['query' => 'SKU-ODOO-001'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('source', 'odoo')
            ->assertJsonPath('product.model', 'SKU-ODOO-001')
            ->assertJsonPath('product.category_name', 'Cookers')
            ->assertJsonPath('product.purchase_date', null)
            ->assertJsonPath('can_register', true);
    }

    public function test_lookup_returns_friendly_message_when_odoo_unavailable(): void
    {
        $mock = Mockery::mock(OdooProductService::class);
        $mock->shouldReceive('searchProduct')->once()->andThrow(new \RuntimeException('Connection timeout'));
        app()->instance(OdooProductService::class, $mock);

        $this->postJson('/api/products/lookup', ['query' => 'SKU-NONE-001'])
            ->assertStatus(503)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'We could not complete the product lookup at the moment. Please try again shortly.')
            ->assertJsonPath('can_register', false);
    }
}

