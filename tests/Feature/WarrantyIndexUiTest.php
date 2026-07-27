<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarrantyIndexUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_warranties_index_renders_improved_ui(): void
    {
        $admin = User::where('email', 'admin@kelec.test')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.warranties.index'))
            ->assertOk()
            ->assertSee('Pending queue')
            ->assertSee('Export CSV')
            ->assertSee('Search name, phone, serial, reference', false);
    }
}