<?php

namespace Tests\Feature;

use App\Enums\WarrantyStatus;
use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use App\Models\Warranty;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_warranty_lookup_writes_activity_log_without_changing_result(): void
    {
        $customer = Customer::factory()->create([
            'mobile_number' => '0712345678',
            'mobile_normalized' => '254712345678',
        ]);
        $warranty = Warranty::factory()->create([
            'customer_id' => $customer->id,
            'serial_number' => 'ACTLOGSN001',
            'status' => WarrantyStatus::Active,
        ]);

        $this->postJson(route('api.warranties.lookup'), [
            'serial_number' => 'ACTLOGSN001',
            'mobile_number' => '0712345678',
        ])->assertOk();

        $this->assertDatabaseHas('activity_logs', [
            'type' => 'warranty_lookup',
            'action' => 'lookup',
            'status' => 'found',
            'query' => 'ACTLOGSN001',
            'reference' => $warranty->reference,
        ]);
    }

    public function test_product_lookup_writes_activity_log(): void
    {
        Product::factory()->create([
            'barcode' => 'ACT-PRODUCT-1',
            'name' => 'Activity Log Product',
            'display_name' => 'Activity Log Product',
            'serial_number' => 'ACT-PRODUCT-SN',
        ]);

        $this->postJson(route('api.products.lookup'), [
            'query' => 'ACT-PRODUCT-1',
        ])->assertOk();

        $this->assertDatabaseHas('activity_logs', [
            'type' => 'product_lookup',
            'action' => 'lookup',
            'status' => 'found',
            'query' => 'ACT-PRODUCT-1',
        ]);
    }

    public function test_admin_can_view_activity_logs(): void
    {
        ActivityLog::query()->create([
            'type' => 'warranty_lookup',
            'action' => 'lookup',
            'status' => 'found',
            'query' => 'SN-VIEW-1',
            'result_summary' => 'Matched warranty',
        ]);

        $admin = User::query()->where('email', 'admin@k-elec.co.ke')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.activity-logs.index'))
            ->assertOk()
            ->assertSee('Activity Logs')
            ->assertSee('SN-VIEW-1');
    }
}
