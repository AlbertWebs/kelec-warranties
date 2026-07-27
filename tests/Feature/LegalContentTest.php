<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SettingsService;
use App\Support\LegalContentDefaults;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalContentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_privacy_policy_page_renders_seeded_content(): void
    {
        $this->get(route('privacy-policy'))
            ->assertOk()
            ->assertSee('Privacy Policy')
            ->assertSee('Who we are', false)
            ->assertSee('Data we collect', false);
    }

    public function test_admin_can_update_privacy_policy_content(): void
    {
        $admin = User::where('email', 'admin@kelec.test')->firstOrFail();

        $this->actingAs($admin)
            ->put(route('admin.legal.update'), [
                'privacy_policy_url' => url('/privacy-policy'),
                'warranty_terms_url' => url('/warranty-terms'),
                'privacy_policy_content' => "## Custom privacy\n\nUpdated by admin for testing.",
                'warranty_terms_content' => LegalContentDefaults::warrantyTerms(),
            ])
            ->assertRedirect();

        $this->get(route('privacy-policy'))
            ->assertOk()
            ->assertSee('Custom privacy')
            ->assertSee('Updated by admin for testing');
    }

    public function test_support_user_cannot_edit_legal_pages(): void
    {
        $support = User::where('email', 'support@kelec.test')->firstOrFail();

        $this->actingAs($support)
            ->get(route('admin.legal.edit'))
            ->assertForbidden();
    }

    public function test_settings_service_serves_updated_privacy_content(): void
    {
        app(SettingsService::class)->set(
            'privacy_policy_content',
            '## Hello privacy',
            'privacy'
        );

        $this->get(route('privacy-policy'))
            ->assertOk()
            ->assertSee('Hello privacy');
    }
}
