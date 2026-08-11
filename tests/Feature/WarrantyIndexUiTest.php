<?php

namespace Tests\Feature;

use App\Enums\WarrantyStatus;
use App\Models\User;
use App\Models\Warranty;
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
        $admin = User::where('email', 'admin@k-elec.co.ke')->firstOrFail();
        Warranty::factory()->create([
            'status' => WarrantyStatus::Active,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.warranties.index'))
            ->assertOk()
            ->assertSee('Pending queue')
            ->assertSee('Export CSV')
            ->assertSee('Notify')
            ->assertSee('Search name, phone, serial, reference', false);
    }

    public function test_warranties_index_shows_notification_count_when_notified(): void
    {
        $admin = User::where('email', 'admin@k-elec.co.ke')->firstOrFail();
        $warranty = Warranty::factory()->create([
            'status' => WarrantyStatus::Active,
        ]);

        \App\Models\NotificationLog::create([
            'warranty_id' => $warranty->id,
            'customer_id' => $warranty->customer_id,
            'notification_type' => 'warranty_activated',
            'channel' => \App\Enums\NotificationChannel::Email,
            'recipient' => 'customer@example.com',
            'message' => 'Test',
            'status' => 'sent',
            'sent_at' => now(),
            'retry_count' => 0,
        ]);
        \App\Models\NotificationLog::create([
            'warranty_id' => $warranty->id,
            'customer_id' => $warranty->customer_id,
            'notification_type' => 'warranty_activated',
            'channel' => \App\Enums\NotificationChannel::Sms,
            'recipient' => '254712345678',
            'message' => 'Test',
            'status' => 'sent',
            'sent_at' => now(),
            'retry_count' => 0,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.warranties.index'))
            ->assertOk()
            ->assertSee('Notified 1×', false)
            ->assertSee('Notify again');
    }

    public function test_admin_can_send_notification_from_warranties_index(): void
    {
        $admin = User::where('email', 'admin@k-elec.co.ke')->firstOrFail();
        $warranty = Warranty::factory()->create([
            'status' => WarrantyStatus::Active,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.warranties.index'))
            ->post(route('admin.warranties.resend', $warranty))
            ->assertRedirect(route('admin.warranties.index'))
            ->assertSessionHas('success', 'Notification sent to the customer.');
    }
}
