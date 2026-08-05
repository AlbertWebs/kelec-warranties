<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSidebarPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_support_sidebar_hides_admin_only_links(): void
    {
        $support = User::where('email', 'support@kelec.test')->firstOrFail();

        $html = $this->actingAs($support)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('All warranties', $html);
        $this->assertStringContainsString('Customers', $html);
        $this->assertStringNotContainsString('href="'.route('admin.users.index').'"', $html);
        $this->assertStringNotContainsString('href="'.route('admin.settings.edit').'"', $html);
        $this->assertStringNotContainsString('href="'.route('admin.roles.index').'"', $html);
        $this->assertStringNotContainsString('href="'.route('admin.reports.index').'"', $html);
        $this->assertStringNotContainsString('>Odoo sync</span>', $html);
        $this->assertStringNotContainsString('>Reports</span>', $html);
    }

    public function test_warranty_admin_sidebar_shows_users_but_hides_settings(): void
    {
        $admin = User::where('email', 'warranty@kelec.test')->firstOrFail();

        $html = $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('href="'.route('admin.users.index').'"', $html);
        $this->assertStringContainsString('href="'.route('admin.odoo.index').'"', $html);
        $this->assertStringNotContainsString('href="'.route('admin.settings.edit').'"', $html);
        $this->assertStringNotContainsString('href="'.route('admin.roles.index').'"', $html);
    }
}
