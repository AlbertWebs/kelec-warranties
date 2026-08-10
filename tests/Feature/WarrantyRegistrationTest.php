<?php

namespace Tests\Feature;

use App\Enums\WarrantyStatus;
use App\Models\Product;
use App\Models\PurchaseSource;
use App\Models\User;
use App\Models\Warranty;
use App\Services\SettingsService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WarrantyRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $settings = app(SettingsService::class);
        $settings->set('odoo_mock_mode', true, 'odoo', 'boolean');
        $settings->set('odoo_enabled', false, 'odoo', 'boolean');
        $settings->set('sms_enabled', false, 'sms', 'boolean');
    }

    public function test_customer_can_register_warranty_with_validated_serial(): void
    {
        $source = PurchaseSource::where('code', 'brand_shop')->firstOrFail();
        $product = Product::firstOrFail();

        $response = $this->post(route('register-warranty.store'), [
            'serial_number' => 'KEVALID123',
            'full_name' => 'Jane Customer',
            'mobile_number' => '0712345678',
            'email' => 'jane@example.com',
            'purchase_source_id' => $source->id,
            'product_id' => $product->id,
            'purchase_date' => now()->subDays(3)->toDateString(),
            'privacy_accepted' => '1',
            'marketing_consent' => '0',
        ]);

        $warranty = Warranty::first();
        $this->assertNotNull($warranty);
        $response->assertRedirect(route('register-warranty.success', $warranty->reference));
        $this->assertTrue($warranty->privacy_accepted);
        $this->assertFalse($warranty->marketing_consent);
        $this->assertEquals(WarrantyStatus::Active, $warranty->status);
        $this->assertStringStartsWith('KEL-WTY-', $warranty->reference);
    }

    public function test_missing_serial_creates_pending_verification(): void
    {
        $source = PurchaseSource::where('code', 'dealer')->firstOrFail();

        $this->post(route('register-warranty.store'), [
            'serial_number' => 'NOTFOUND999',
            'full_name' => 'John Doe',
            'mobile_number' => '0722000000',
            'purchase_source_id' => $source->id,
            'product_name' => 'Unknown Appliance',
            'purchase_date' => now()->subDays(2)->toDateString(),
            'privacy_accepted' => '1',
        ])->assertRedirect();

        $warranty = Warranty::where('serial_number', 'NOTFOUND999')->firstOrFail();
        $this->assertEquals(WarrantyStatus::PendingVerification, $warranty->status);
        $this->assertTrue($warranty->requires_manual_verification);
    }

    public function test_duplicate_active_warranty_is_blocked(): void
    {
        $source = PurchaseSource::where('code', 'brand_shop')->firstOrFail();
        $product = Product::firstOrFail();

        $this->post(route('register-warranty.store'), [
            'serial_number' => 'DUPESERIAL01',
            'full_name' => 'First Owner',
            'mobile_number' => '0700111222',
            'purchase_source_id' => $source->id,
            'product_id' => $product->id,
            'purchase_date' => now()->subDay()->toDateString(),
            'privacy_accepted' => '1',
        ])->assertRedirect();

        $this->post(route('register-warranty.store'), [
            'serial_number' => 'DUPESERIAL01',
            'full_name' => 'Second Owner',
            'mobile_number' => '0700333444',
            'purchase_source_id' => $source->id,
            'product_id' => $product->id,
            'purchase_date' => now()->subDay()->toDateString(),
            'privacy_accepted' => '1',
        ])->assertStatus(422);

        $this->assertEquals(1, Warranty::where('serial_number', 'DUPESERIAL01')->count());
    }

    public function test_marketing_consent_is_optional_and_defaults_false(): void
    {
        $source = PurchaseSource::firstOrFail();

        $this->post(route('register-warranty.store'), [
            'serial_number' => 'CONSENTTEST1',
            'full_name' => 'Consent Tester',
            'mobile_number' => '0711000000',
            'purchase_source_id' => $source->id,
            'purchase_date' => now()->toDateString(),
            'privacy_accepted' => '1',
        ])->assertRedirect();

        $warranty = Warranty::where('serial_number', 'CONSENTTEST1')->firstOrFail();
        $this->assertFalse($warranty->marketing_consent);
    }

    public function test_privacy_acceptance_is_required(): void
    {
        $source = PurchaseSource::firstOrFail();

        $this->from(route('register-warranty.create'))
            ->post(route('register-warranty.store'), [
                'serial_number' => 'NOPRIVACY01',
                'full_name' => 'No Privacy',
                'mobile_number' => '0711222333',
                'purchase_source_id' => $source->id,
                'purchase_date' => now()->toDateString(),
            ])
            ->assertSessionHasErrors('privacy_accepted');
    }

    public function test_receipt_upload_is_validated_and_stored_privately(): void
    {
        Storage::fake('local');
        $source = PurchaseSource::firstOrFail();

        $this->post(route('register-warranty.store'), [
            'serial_number' => 'RECEIPTTEST1',
            'full_name' => 'Receipt Owner',
            'mobile_number' => '0711555666',
            'purchase_source_id' => $source->id,
            'purchase_date' => now()->toDateString(),
            'privacy_accepted' => '1',
            'receipt' => UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf'),
        ])->assertRedirect();

        $warranty = Warranty::where('serial_number', 'RECEIPTTEST1')->firstOrFail();
        $this->assertNotNull($warranty->receipt_path);
        Storage::disk('local')->assertExists($warranty->receipt_path);
    }

    public function test_lookup_requires_mobile_and_returns_masked_details(): void
    {
        $warranty = Warranty::factory()->create([
            'status' => WarrantyStatus::Active,
            'serial_number' => 'LOOKUP12345',
        ]);

        $response = $this->post(route('warranty.lookup.store'), [
            'serial_number' => 'LOOKUP12345',
            'mobile_number' => $warranty->customer->mobile_number,
        ]);

        $response->assertOk();
        $response->assertSee($warranty->reference);
        $response->assertSee($warranty->customer->maskedMobile());
        $response->assertDontSee($warranty->customer->mobile_normalized);
    }

    public function test_support_user_cannot_access_settings(): void
    {
        $user = User::where('email', 'support@kelec.test')->firstOrFail();

        $this->actingAs($user)
            ->get(route('admin.settings.edit'))
            ->assertForbidden();
    }

    public function test_warranty_admin_can_approve_pending_warranty(): void
    {
        $admin = User::where('email', 'warranty@kelec.test')->firstOrFail();
        $warranty = Warranty::factory()->create([
            'status' => WarrantyStatus::PendingVerification,
            'warranty_start_date' => null,
            'warranty_expiry_date' => null,
            'requires_manual_verification' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.warranties.approve', $warranty))
            ->assertRedirect();

        $warranty->refresh();
        $this->assertEquals(WarrantyStatus::Active, $warranty->status);
        $this->assertNotNull($warranty->warranty_expiry_date);
    }

    public function test_serial_check_redirects_existing_active_warranty_to_lookup(): void
    {
        Warranty::factory()->create([
            'serial_number' => 'EXISTINGACTIVE',
            'status' => WarrantyStatus::Active,
        ]);

        $this->post(route('register-warranty.serial-check'), [
            'serial_number' => 'EXISTINGACTIVE',
        ])->assertRedirect(route('warranty.lookup'));
    }

    public function test_registration_uses_local_serial_cache_before_odoo(): void
    {
        $settings = app(SettingsService::class);
        $settings->set('odoo_mock_mode', false, 'odoo', 'boolean');
        $settings->set('odoo_enabled', true, 'odoo', 'boolean');

        $mock = \Mockery::mock(\App\Services\Odoo\OdooProductService::class);
        $mock->shouldReceive('lookupBySerial')->andReturn([
            'found' => false,
            'message' => 'Serial number not found in Odoo.',
        ]);
        app()->instance(\App\Services\Odoo\OdooProductService::class, $mock);

        $source = PurchaseSource::where('code', 'brand_shop')->firstOrFail();
        $product = Product::firstOrFail();
        $product->update([
            'serial_number' => 'LOCALCACHE001',
            'default_code' => 'LOCALCACHE001',
        ]);

        $this->post(route('register-warranty.store'), [
            'serial_number' => 'LOCALCACHE001',
            'full_name' => 'Local Cache Customer',
            'mobile_number' => '0712999000',
            'purchase_source_id' => $source->id,
            'product_id' => $product->id,
            'purchase_date' => now()->subDays(2)->toDateString(),
            'privacy_accepted' => '1',
        ])->assertRedirect();

        $warranty = Warranty::latest()->firstOrFail();
        $this->assertEquals(WarrantyStatus::Active, $warranty->status);
        $this->assertTrue($warranty->odoo_validated);
        $this->assertStringContainsString('Product found', (string) $warranty->odoo_validation_message);
    }
}
