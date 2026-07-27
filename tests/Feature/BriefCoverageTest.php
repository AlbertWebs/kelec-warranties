<?php

namespace Tests\Feature;

use App\Enums\WarrantyStatus;
use App\Models\Product;
use App\Models\PublicAccessToken;
use App\Models\Warranty;
use App\Services\PosWarrantyImportService;
use App\Services\SettingsService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BriefCoverageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $settings = app(SettingsService::class);
        $settings->set('odoo_mock_mode', true, 'odoo', 'boolean');
        $settings->set('sms_enabled', false, 'sms', 'boolean');
        $settings->set('pos_brand_shop_branches', 'Sarin,CBD', 'odoo');
    }

    public function test_brand_shop_pos_sale_creates_active_warranty_for_sarin(): void
    {
        $product = Product::firstOrFail();

        $response = $this->postJson('/api/odoo/pos-sale', [
            'serial_number' => 'POSSARIN001',
            'branch_name' => 'Sarin',
            'odoo_pos_order_id' => 'POS-1001',
            'full_name' => 'POS Customer',
            'mobile_number' => '0712345670',
            'product_id' => $product->id,
            'purchase_date' => now()->toDateString(),
            'marketing_consent' => false,
        ]);

        $response->assertOk()->assertJsonPath('status', 'active');

        $warranty = Warranty::where('serial_number', 'POSSARIN001')->firstOrFail();
        $this->assertEquals(WarrantyStatus::Active, $warranty->status);
        $this->assertEquals('odoo_pos', $warranty->registration_source->value);
        $this->assertFalse($warranty->marketing_consent);
        $this->assertTrue(
            PublicAccessToken::query()
                ->where('type', 'marketing_consent')
                ->where('customer_id', $warranty->customer_id)
                ->exists()
        );
    }

    public function test_pos_import_rejects_unconfigured_branch(): void
    {
        $this->postJson('/api/odoo/pos-sale', [
            'serial_number' => 'POSOTHER001',
            'branch_name' => 'Westlands',
            'full_name' => 'Someone',
            'mobile_number' => '0712345671',
        ])->assertStatus(422);
    }

    public function test_incomplete_pos_customer_creates_provisional_completion_link(): void
    {
        $service = app(PosWarrantyImportService::class);

        $warranty = $service->import([
            'serial_number' => 'POSPROV001',
            'branch_name' => 'CBD',
            'odoo_pos_order_id' => 'POS-2002',
            'product_name' => 'K-Elec Cooker 1000',
            'purchase_date' => now()->toDateString(),
        ]);

        $this->assertTrue($warranty->requires_manual_verification);
        $this->assertEquals(WarrantyStatus::PendingVerification, $warranty->status);

        $token = PublicAccessToken::query()
            ->where('type', 'complete_registration')
            ->where('warranty_id', $warranty->id)
            ->firstOrFail();

        $this->post(route('complete-registration.store', $token->token), [
            'full_name' => 'Completed Customer',
            'mobile_number' => '0722333444',
            'email' => 'done@example.com',
        ])->assertRedirect();

        $warranty->refresh();
        $this->assertEquals(WarrantyStatus::Active, $warranty->status);
        $this->assertEquals('Completed Customer', $warranty->customer->fresh()->full_name);
    }

    public function test_marketing_consent_link_is_optional_and_defaults_unticked(): void
    {
        $warranty = Warranty::factory()->create();
        $token = PublicAccessToken::issue('marketing_consent', $warranty->customer, $warranty);

        $this->get(route('consent.show', $token->token))
            ->assertOk()
            ->assertSee('optional and does not affect your warranty');

        $this->post(route('consent.store', $token->token), [
            // intentionally omit marketing_consent checkbox
        ])->assertRedirect();

        $this->assertFalse($warranty->customer->fresh()->marketing_consent);
    }

    public function test_duplicate_pos_order_does_not_create_second_warranty(): void
    {
        $payload = [
            'serial_number' => 'POSDUP001',
            'branch_name' => 'Sarin',
            'odoo_pos_order_id' => 'POS-DUP-1',
            'full_name' => 'Dup Customer',
            'mobile_number' => '0700999888',
        ];

        app(PosWarrantyImportService::class)->import($payload);
        app(PosWarrantyImportService::class)->import($payload);

        $this->assertEquals(1, Warranty::where('odoo_pos_order_id', 'POS-DUP-1')->count());
    }
}
