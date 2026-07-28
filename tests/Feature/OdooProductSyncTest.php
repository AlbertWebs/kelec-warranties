<?php

namespace Tests\Feature;

use App\Jobs\SyncOdooProducts;
use App\Models\OdooProductSyncFailure;
use App\Models\OdooProductSyncRun;
use App\Models\Product;
use App\Models\User;
use App\Services\Odoo\OdooProductService;
use App\Services\ProductSyncService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class OdooProductSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_admin_can_queue_product_sync(): void
    {
        Queue::fake();
        $admin = User::where('email', 'admin@kelec.test')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.odoo.products.sync'), [
                'sync_type' => 'full',
                'confirm_full' => 'yes',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('odoo_product_sync_runs', [
            'sync_type' => 'full',
            'status' => 'pending',
        ]);
        Queue::assertPushed(SyncOdooProducts::class);
    }

    public function test_sync_service_updates_records_and_tracks_failures(): void
    {
        Product::factory()->create(['odoo_id' => '101', 'default_code' => 'SKU-101']);

        $odooMock = Mockery::mock(OdooProductService::class);
        $odooMock->shouldReceive('fetchProductsBatch')
            ->once()
            ->andReturn([
                ['id' => 101, 'name' => 'Updated Product', 'default_code' => 'SKU-101'],
                ['id' => 202, 'name' => 'Broken Product', 'default_code' => 'SKU-202'],
            ]);
        $odooMock->shouldReceive('upsertProductFromOdoo')
            ->once()
            ->withArgs(fn ($p) => (int) $p['id'] === 101)
            ->andReturnUsing(fn () => Product::query()->where('odoo_id', '101')->firstOrFail());
        $odooMock->shouldReceive('upsertProductFromOdoo')
            ->once()
            ->withArgs(fn ($p) => (int) $p['id'] === 202)
            ->andThrow(new \RuntimeException('Invalid Odoo payload'));
        $service = new ProductSyncService($odooMock);
        $run = OdooProductSyncRun::create([
            'sync_type' => 'full',
            'status' => 'pending',
            'started_at' => now(),
        ]);

        $service->runSync($run->id);
        $run->refresh();

        $this->assertEquals('completed_with_errors', $run->status);
        $this->assertEquals(2, $run->processed_records);
        $this->assertEquals(1, $run->updated_records);
        $this->assertEquals(1, $run->failed_records);
        $this->assertDatabaseHas('odoo_product_sync_failures', [
            'sync_run_id' => $run->id,
            'external_id' => '202',
            'status' => 'pending',
        ]);
    }

    public function test_retry_failure_marks_record_resolved_when_successful(): void
    {
        $run = OdooProductSyncRun::create([
            'sync_type' => 'incremental',
            'status' => 'completed_with_errors',
            'started_at' => now(),
            'completed_at' => now(),
        ]);
        $failure = OdooProductSyncFailure::create([
            'sync_run_id' => $run->id,
            'external_id' => '500',
            'identifier' => 'SKU-500',
            'error_message' => 'Network issue',
            'status' => 'pending',
            'payload' => ['id' => 500],
        ]);

        $odooMock = Mockery::mock(OdooProductService::class);
        $odooMock->shouldReceive('fetchSingleProduct')->once()->andReturn([
            'id' => 500,
            'name' => 'Recovered Product',
            'default_code' => 'SKU-500',
            'barcode' => '500500500',
            'active' => true,
            'sale_ok' => true,
            'purchase_ok' => false,
        ]);
        $odooMock->shouldReceive('upsertProductFromOdoo')->once()->andReturn(Product::factory()->create(['odoo_id' => '500']));
        (new ProductSyncService($odooMock))->retryFailure($failure);
        $failure->refresh();

        $this->assertEquals('resolved', $failure->status);
        $this->assertEquals(1, $failure->retry_count);
    }
}

