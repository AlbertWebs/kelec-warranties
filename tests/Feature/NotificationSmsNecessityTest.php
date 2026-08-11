<?php

namespace Tests\Feature;

use App\Enums\NotificationChannel;
use App\Models\Customer;
use App\Models\NotificationLog;
use App\Models\Warranty;
use App\Services\NotificationDispatcher;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NotificationSmsNecessityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake([
            'quicksms.advantasms.com/*' => Http::response([
                'responses' => [[
                    'respose-code' => 200,
                    'response-description' => 'Success',
                    'mobile' => 254712345678,
                    'messageid' => 9001,
                    'networkid' => '1',
                ]],
            ], 200),
        ]);

        $settings = app(SettingsService::class);
        $settings->set('sms_enabled', true, 'sms', 'boolean');
        $settings->set('sms_api_key', 'test-key', 'sms', 'string', true);
        $settings->set('sms_partner_id', '123', 'sms', 'string');
        $settings->set('sms_sender_id', 'KELEC', 'sms', 'string');
    }

    public function test_pending_verification_does_not_send_sms(): void
    {
        $warranty = $this->makeWarranty();

        app(NotificationDispatcher::class)->sendNow($warranty, 'warranty_pending_verification');

        $this->assertSame(0, NotificationLog::query()->where('channel', NotificationChannel::Sms)->count());
        $this->assertDatabaseMissing('sms_logs', [
            'mobile' => $warranty->customer->mobile_normalized,
            'context' => 'warranty_pending_verification',
        ]);
    }

    public function test_activated_sends_sms_once_and_skips_duplicate(): void
    {
        $warranty = $this->makeWarranty();
        $dispatcher = app(NotificationDispatcher::class);

        $dispatcher->sendNow($warranty, 'warranty_activated');
        $dispatcher->sendNow($warranty, 'warranty_activated');

        $this->assertSame(1, NotificationLog::query()
            ->where('channel', NotificationChannel::Sms)
            ->where('notification_type', 'warranty_activated')
            ->where('status', 'sent')
            ->count());
    }

    public function test_resend_forces_sms_even_if_already_sent(): void
    {
        $warranty = $this->makeWarranty();
        $dispatcher = app(NotificationDispatcher::class);

        $dispatcher->sendNow($warranty, 'warranty_activated');
        $dispatcher->resend($warranty, 'warranty_activated');

        $this->assertSame(2, NotificationLog::query()
            ->where('channel', NotificationChannel::Sms)
            ->where('notification_type', 'warranty_activated')
            ->where('status', 'sent')
            ->count());
    }

    public function test_marketing_consent_custom_message_skips_sms(): void
    {
        $customer = Customer::factory()->create();
        $warranty = Warranty::factory()->create(['customer_id' => $customer->id]);

        app(NotificationDispatcher::class)->sendCustomMessage(
            $customer,
            $warranty,
            'consent_request',
            'Marketing',
            'Email body',
            'SMS body',
            allowSms: false,
        );

        $this->assertSame(0, NotificationLog::query()->where('channel', NotificationChannel::Sms)->count());
        $this->assertSame(1, NotificationLog::query()->where('channel', NotificationChannel::Email)->count());
    }

    public function test_activated_sms_excludes_lookup_link(): void
    {
        $warranty = $this->makeWarranty();

        app(NotificationDispatcher::class)->sendNow($warranty, 'warranty_activated');

        $log = NotificationLog::query()
            ->where('channel', NotificationChannel::Sms)
            ->where('notification_type', 'warranty_activated')
            ->firstOrFail();

        $this->assertStringNotContainsString('Lookup:', (string) $log->message);
        $this->assertStringNotContainsString('warranty-lookup', (string) $log->message);
        $this->assertStringNotContainsString('registered mobile', (string) $log->message);
        $this->assertStringContainsString('Expiry', (string) $log->message);
        $this->assertMatchesRegularExpression(
            '/^K-Elec: Warranty .+ for .+ is .+\. Expiry .+\.$/',
            trim((string) $log->message)
        );
    }

    public function test_activated_email_uses_support_phone_from_settings(): void
    {
        app(SettingsService::class)->set('support_phone', '0716052243', 'general', 'string');
        app(SettingsService::class)->set('support_email', 'support@k-elec.co.ke', 'general', 'string');

        $warranty = $this->makeWarranty();
        app(NotificationDispatcher::class)->sendNow($warranty, 'warranty_activated');

        $log = NotificationLog::query()
            ->where('channel', NotificationChannel::Email)
            ->where('notification_type', 'warranty_activated')
            ->firstOrFail();

        $this->assertStringContainsString('0716 052 243', (string) $log->message);
        $this->assertStringContainsString('support@k-elec.co.ke', (string) $log->message);
        $this->assertStringNotContainsString('+254700000000', (string) $log->message);
    }

    protected function makeWarranty(): Warranty
    {
        $customer = Customer::factory()->create([
            'mobile_number' => '0712345678',
            'mobile_normalized' => '254712345678',
            'email' => 'customer@example.com',
        ]);

        return Warranty::factory()->create(['customer_id' => $customer->id])->fresh(['customer', 'product']);
    }
}
