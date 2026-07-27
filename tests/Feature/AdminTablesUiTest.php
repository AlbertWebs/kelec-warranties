<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTablesUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_key_admin_list_pages_render_shared_table_styles(): void
    {
        $admin = User::where('email', 'admin@kelec.test')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.customers.index'))
            ->assertOk()
            ->assertSee('Search name, phone, or email', false)
            ->assertSee('bg-slate-50/80', false);

        $this->actingAs($admin)
            ->get(route('admin.claims.index'))
            ->assertOk()
            ->assertSee('Warranty Claims')
            ->assertSee('bg-slate-50/80', false);

        foreach ([
            'admin.dashboard',
            'admin.warranties.index',
            'admin.warranties.pending',
            'admin.products.index',
            'admin.product-categories.index',
            'admin.dealers.index',
            'admin.purchase-sources.index',
            'admin.notifications.index',
            'admin.audit-logs.index',
            'admin.users.index',
            'admin.odoo.index',
        ] as $route) {
            $this->actingAs($admin)
                ->get(route($route))
                ->assertOk();
        }
    }
}