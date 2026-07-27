<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use App\Models\Warranty;
use App\Models\WarrantyClaim;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_super_admin_can_view_and_seed_demo_data(): void
    {
        $admin = User::where('email', 'admin@kelec.test')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.demo-data.show'))
            ->assertOk();

        $this->actingAs($admin)
            ->post(route('admin.demo-data.seed'))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertGreaterThan(0, Customer::query()->count());
        $this->assertGreaterThan(0, Warranty::query()->count());
        $this->assertGreaterThan(0, WarrantyClaim::query()->count());
    }

    public function test_super_admin_can_wipe_demo_data_with_confirmation(): void
    {
        $admin = User::where('email', 'admin@kelec.test')->firstOrFail();

        $this->actingAs($admin)->post(route('admin.demo-data.seed'));

        $this->actingAs($admin)
            ->post(route('admin.demo-data.wipe'), [
                'password' => 'password',
                'confirm' => 'DELETE',
            ])
            ->assertRedirect(route('admin.demo-data.show'))
            ->assertSessionHas('success');

        $this->assertSame(0, Customer::query()->count());
        $this->assertSame(0, Warranty::query()->count());
        $this->assertSame(0, WarrantyClaim::query()->count());
        $this->assertDatabaseHas('users', ['email' => 'admin@kelec.test']);
    }

    public function test_non_super_admin_cannot_access_danger_zone(): void
    {
        $support = User::where('email', 'support@kelec.test')->firstOrFail();

        $this->actingAs($support)
            ->get(route('admin.demo-data.show'))
            ->assertForbidden();
    }
}
