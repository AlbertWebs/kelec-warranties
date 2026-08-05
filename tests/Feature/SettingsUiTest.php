<?php

namespace Tests\Feature;

use App\Mail\SettingsTestMail;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SettingsUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_settings_page_renders_premium_layout(): void
    {
        $admin = User::where('email', 'admin@k-elec.co.ke')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertSee('System settings')
            ->assertSee('Save settings')
            ->assertSee('Odoo integration')
            ->assertSee('Send test email')
            ->assertSee('Open SMS settings');
    }

    public function test_admin_can_dispatch_test_email_from_settings(): void
    {
        Mail::fake();
        $admin = User::where('email', 'admin@k-elec.co.ke')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.settings.test-email'), [
                'to' => 'ops@example.com',
                'subject' => 'Portal test',
                'body' => 'Hello from settings',
            ])
            ->assertRedirect(route('admin.settings.edit', ['tab' => 'email']))
            ->assertSessionHas('success');

        Mail::assertSent(SettingsTestMail::class, function (SettingsTestMail $mail) {
            return $mail->hasTo('ops@example.com')
                && $mail->emailSubject === 'Portal test';
        });
    }
}
