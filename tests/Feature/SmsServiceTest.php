<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SettingsService;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SmsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['sms.view', 'sms.manage', 'settings.manage'] as $permission) {
            Permission::findOrCreate($permission);
        }

        $role = Role::findOrCreate('super_admin');
        $role->syncPermissions(Permission::all());
    }

    public function test_send_sms_helper_mocks_when_disabled(): void
    {
        $settings = app(SettingsService::class);
        $settings->set('sms_enabled', false, 'sms', 'boolean');

        $result = send_sms('0712345678', 'Hello from test', 'unit_test');

        $this->assertTrue($result['ok']);
        $this->assertSame('sms_mock_sent', $result['response']);
        $this->assertDatabaseHas('sms_logs', [
            'mobile' => '254712345678',
            'status' => 'mock',
            'context' => 'unit_test',
        ]);
    }

    public function test_advanta_send_posts_expected_payload(): void
    {
        Http::fake([
            'quicksms.advantasms.com/*' => Http::response([
                'responses' => [[
                    'respose-code' => 200,
                    'response-description' => 'Success',
                    'mobile' => 254712345678,
                    'messageid' => 8290842,
                    'networkid' => '1',
                ]],
            ], 200),
        ]);

        $settings = app(SettingsService::class);
        $settings->set('sms_enabled', true, 'sms', 'boolean');
        $settings->set('sms_api_key', 'test-key', 'sms', 'string', true);
        $settings->set('sms_partner_id', '123', 'sms', 'string');
        $settings->set('sms_sender_id', 'KELEC', 'sms', 'string');

        $result = app(SmsService::class)->send('0712345678', 'Warranty active', 'warranty_activated');

        $this->assertTrue($result['ok']);
        $this->assertSame('8290842', $result['message_id']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://quicksms.advantasms.com/api/services/sendsms/'
                && $request['apikey'] === 'test-key'
                && $request['partnerID'] === '123'
                && $request['shortcode'] === 'KELEC'
                && $request['mobile'] === '254712345678'
                && $request['message'] === 'Warranty active';
        });

        $this->assertDatabaseHas('sms_logs', [
            'provider_message_id' => '8290842',
            'status' => 'sent',
            'response_code' => 200,
        ]);
    }

    public function test_admin_sms_page_requires_permission(): void
    {
        $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
        $user->givePermissionTo('sms.view');
        $user->assignRole('super_admin');

        $this->actingAs($user)
            ->get(route('admin.sms.index'))
            ->assertOk()
            ->assertSee('AdvantaSMS');
    }

    public function test_get_balance_uses_advanta_endpoint(): void
    {
        Http::fake([
            'quicksms.advantasms.com/*' => Http::response(['balance' => '150.50', 'response-code' => 200], 200),
        ]);

        $settings = app(SettingsService::class);
        $settings->set('sms_enabled', true, 'sms', 'boolean');
        $settings->set('sms_api_key', 'test-key', 'sms', 'string', true);
        $settings->set('sms_partner_id', '123', 'sms', 'string');
        $settings->set('sms_sender_id', 'KELEC', 'sms', 'string');

        $result = app(SmsService::class)->getBalance();

        $this->assertTrue($result['ok']);
        $this->assertSame('150.50', $result['balance']);
    }
}
