<?php

namespace Tests\Feature;

use App\Enums\WarrantyStatus;
use App\Models\Product;
use App\Models\Warranty;
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

    public function test_serial_check_matches_local_product_by_unit_serial_only(): void
    {
        Product::factory()->create([
            'barcode' => 'ABC-9001',
            'default_code' => 'SKU-9001',
            'model' => 'SKU-9001',
            'serial_number' => 'UNIT-SERIAL-9001',
            'name' => 'K-Elec Cooker',
        ]);

        $result = app(WarrantyRegistrationService::class)->checkSerial('UNIT-SERIAL-9001');

        $this->assertSame('found_local', $result['status']);
        $this->assertTrue($result['odoo']['found']);
    }

    public function test_serial_check_does_not_treat_sku_or_barcode_as_unit_serial(): void
    {
        Product::factory()->create([
            'barcode' => 'ABC-9001',
            'default_code' => 'SKU-9001',
            'model' => 'SKU-9001',
            'serial_number' => null,
            'name' => 'K-Elec Cooker',
        ]);

        $mock = Mockery::mock(OdooProductService::class);
        $mock->shouldReceive('lookupBySerial')->once()->with('SKU-9001')->andReturn([
            'found' => false,
            'message' => 'Serial number not found in Odoo.',
        ]);
        app()->instance(OdooProductService::class, $mock);

        $result = app(WarrantyRegistrationService::class)->checkSerial('SKU-9001');

        $this->assertSame('not_found', $result['status']);
    }

    public function test_existing_warranty_only_blocks_matching_unit_serial(): void
    {
        Product::factory()->create([
            'barcode' => 'BAR-SHARED',
            'default_code' => 'MODEL-1',
            'serial_number' => null,
        ]);

        Warranty::factory()->create([
            'serial_number' => 'REAL-UNIT-001',
            'status' => WarrantyStatus::Active,
        ]);

        $mock = Mockery::mock(OdooProductService::class);
        $mock->shouldReceive('lookupBySerial')->once()->with('BAR-SHARED')->andReturn([
            'found' => false,
            'message' => 'Serial number not found in Odoo.',
        ]);
        app()->instance(OdooProductService::class, $mock);

        $byBarcode = app(WarrantyRegistrationService::class)->checkSerial('BAR-SHARED');
        $this->assertSame('not_found', $byBarcode['status']);

        $byRegisteredSerial = app(WarrantyRegistrationService::class)->checkSerial('REAL-UNIT-001');
        $this->assertSame('existing_active', $byRegisteredSerial['status']);
    }

    public function test_serial_check_does_not_accept_odoo_catalog_product_as_serial(): void
    {
        $mock = Mockery::mock(OdooProductService::class);
        $mock->shouldReceive('lookupBySerial')->once()->with('SKU-FALLBACK-1')->andReturn([
            'found' => false,
            'message' => 'Serial number not found in Odoo.',
        ]);
        app()->instance(OdooProductService::class, $mock);

        $result = app(WarrantyRegistrationService::class)->checkSerial('SKU-FALLBACK-1');

        $this->assertSame('not_found', $result['status']);
    }
}
