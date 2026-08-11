<?php

namespace Tests\Feature;

use App\Enums\WarrantyStatus;
use App\Models\Product;
use App\Models\Warranty;
use App\Services\SettingsService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SerialCheckAjaxTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $settings = app(SettingsService::class);
        $settings->set('odoo_mock_mode', true, 'odoo', 'boolean');
        $settings->set('odoo_enabled', false, 'odoo', 'boolean');
    }

    public function test_ajax_serial_check_returns_json_for_local_product(): void
    {
        Product::factory()->create([
            'barcode' => 'AJAX-BARCODE-1',
            'name' => 'Ajax Cooker',
            'display_name' => 'Ajax Cooker',
            'model' => 'AX-1',
            'serial_number' => 'AJAX-SERIAL-1',
        ]);

        $this->postJson(route('register-warranty.serial-check'), [
            'serial_number' => 'AJAX-SERIAL-1',
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('validated', true)
            ->assertJsonPath('prefill.product_name', 'Ajax Cooker')
            ->assertJsonPath('prefill.serial_number', 'AJAX-SERIAL-1');
    }

    public function test_ajax_serial_check_prefills_customer_details_from_odoo_mock(): void
    {
        $this->postJson(route('register-warranty.serial-check'), [
            'serial_number' => 'MOCK-CUST-123',
        ])
            ->assertOk()
            ->assertJsonPath('validated', true)
            ->assertJsonPath('prefill.full_name', 'Mock Prefill Customer')
            ->assertJsonPath('prefill.mobile_number', '0711111111')
            ->assertJsonPath('prefill.email', 'mock@example.com')
            ->assertJsonPath('prefill.county', 'Nairobi')
            ->assertJsonPath('prefill.town', 'Westlands');
    }

    public function test_ajax_serial_check_redirects_existing_active_warranty(): void
    {
        Warranty::factory()->create([
            'serial_number' => 'EXISTINGAJAX',
            'status' => WarrantyStatus::Active,
        ]);

        $this->postJson(route('register-warranty.serial-check'), [
            'serial_number' => 'EXISTINGAJAX',
        ])
            ->assertStatus(409)
            ->assertJsonPath('status', 'existing_active')
            ->assertJsonStructure(['redirect_url']);
    }
}