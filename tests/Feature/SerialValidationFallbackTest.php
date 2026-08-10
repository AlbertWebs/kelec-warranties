<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Services\Odoo\OdooProductService;
use App\Services\WarrantyRegistrationService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SerialValidationFallbackTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_serial_check_finds_local_product_by_barcode_case_insensitively(): void
    {
        Product::factory()->create([
            'barcode' => 'abc-9001',
            'default_code' => 'SKU-9001',
            'serial_number' => null,
            'name' => 'K-Elec Cooker',
        ]);

        $result = app(WarrantyRegistrationService::class)->checkSerial('ABC-9001');

        $this->assertSame('found_local', $result['status']);
        $this->assertTrue($result['odoo']['found']);
    }

    public function test_serial_check_falls_back_to_odoo_product_catalog_when_lot_missing(): void
    {
        $mock = Mockery::mock(OdooProductService::class);
        $mock->shouldReceive('lookupBySerial')->once()->with('SKU-FALLBACK-1')->andReturn([
            'found' => true,
            'message' => 'Product matched in Odoo catalog.',
            'product' => [
                'id' => 11,
                'odoo_product_id' => '77',
                'name' => 'Fallback Product',
                'model' => 'SKU-FALLBACK-1',
            ],
            'sale' => [],
            'customer' => null,
        ]);
        app()->instance(OdooProductService::class, $mock);

        $result = app(WarrantyRegistrationService::class)->checkSerial('SKU-FALLBACK-1');

        $this->assertSame('found', $result['status']);
        $this->assertSame('Fallback Product', $result['odoo']['product']['name']);
    }
}