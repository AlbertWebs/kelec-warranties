<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ForbiddenPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_forbidden_page_shows_friendly_message_instead_of_forbidden(): void
    {
        $support = User::where('email', 'support@kelec.test')->firstOrFail();

        $this->actingAs($support)
            ->get(route('admin.users.index'))
            ->assertForbidden()
            ->assertSee('You don’t have access to this page', false)
            ->assertSee('Go back')
            ->assertSee('Go to dashboard')
            ->assertDontSee('Forbidden');
    }
}
