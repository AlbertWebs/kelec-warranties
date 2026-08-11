<?php

namespace Tests\Feature;

use App\Enums\NotificationChannel;
use App\Models\NotificationTemplate;
use App\Services\SettingsService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnableNotificationsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_command_enables_sms_and_activates_both_channels(): void
    {
        app(SettingsService::class)->set('sms_enabled', false, 'sms', 'boolean');

        NotificationTemplate::query()->update([
            'is_active' => false,
            'channel' => NotificationChannel::Email,
        ]);

        $this->artisan('notifications:enable')
            ->assertSuccessful();

        $this->assertTrue((bool) app(SettingsService::class)->get('sms_enabled', false));

        $template = NotificationTemplate::query()->where('key', 'warranty_activated')->firstOrFail();
        $this->assertTrue($template->is_active);
        $this->assertSame(NotificationChannel::Both, $template->channel);
    }

    public function test_command_can_disable_sms(): void
    {
        app(SettingsService::class)->set('sms_enabled', true, 'sms', 'boolean');

        $this->artisan('notifications:enable', ['--disable' => true])
            ->assertSuccessful();

        $this->assertFalse((bool) app(SettingsService::class)->get('sms_enabled', false));
    }
}
