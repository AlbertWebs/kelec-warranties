<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarrantyAdminUsersAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_warranty_administrator_can_access_users_page(): void
    {
        $admin = User::where('email', 'warranty@kelec.test')->firstOrFail();

        $this->assertTrue($admin->can('users.view'));
        $this->assertTrue($admin->can('users.manage'));

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Users')
            ->assertSee('Delete');
    }

    public function test_admin_can_delete_another_user(): void
    {
        $admin = User::where('email', 'admin@k-elec.co.ke')->firstOrFail();
        $target = User::where('email', 'support@kelec.test')->firstOrFail();

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $target))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }

    public function test_admin_cannot_delete_own_account(): void
    {
        $admin = User::where('email', 'admin@k-elec.co.ke')->firstOrFail();

        $this->actingAs($admin)
            ->from(route('admin.users.index'))
            ->delete(route('admin.users.destroy', $admin))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_customer_support_cannot_access_users_page(): void
    {
        $support = User::where('email', 'support@kelec.test')->firstOrFail();

        $this->actingAs($support)
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }
}
