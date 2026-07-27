<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Warranty;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarrantyShowUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->seed(\Database\Seeders\DemoDataSeeder::class);
    }

    public function test_warranty_show_renders_improved_ui(): void
    {
        $admin = User::where('email', 'admin@kelec.test')->firstOrFail();
        $warranty = Warranty::query()->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.warranties.show', $warranty))
            ->assertOk()
            ->assertSee('Back to warranties')
            ->assertSee($warranty->reference)
            ->assertSee('Status history')
            ->assertSee('Staff notes');
    }
}