<?php

namespace Tests\Feature;

use App\Enums\ClaimStatus;
use App\Enums\NotificationChannel;
use App\Enums\WarrantyStatus;
use App\Models\Customer;
use App\Models\NotificationLog;
use App\Models\User;
use App\Models\Warranty;
use App\Models\WarrantyClaim;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminClaimStatusNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        Mail::fake();
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
    }

    public function test_admin_claim_show_renders_improved_layout(): void
    {
        $admin = User::query()->where('email', 'admin@k-elec.co.ke')->firstOrFail();
        $claim = $this->makeClaim();

        $this->actingAs($admin)
            ->get(route('admin.claims.show', $claim))
            ->assertOk()
            ->assertSee($claim->reference)
            ->assertSee('Notify customer')
            ->assertSee('Linked warranty')
            ->assertSee('Save changes')
            ->assertSee('Photos');
    }

    public function test_status_change_notifies_customer_by_default(): void
    {
        $admin = User::query()->where('email', 'admin@k-elec.co.ke')->firstOrFail();
        $claim = $this->makeClaim();

        $this->actingAs($admin)
            ->put(route('admin.claims.update', $claim), [
                'status' => ClaimStatus::InReview->value,
                'admin_notes' => 'We are reviewing this claim.',
                'notify_customer' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(ClaimStatus::InReview, $claim->fresh()->status);

        $this->assertDatabaseHas('notification_logs', [
            'customer_id' => $claim->customer_id,
            'notification_type' => 'claim_status_updated',
            'channel' => NotificationChannel::Email->value,
            'status' => 'sent',
        ]);
    }

    public function test_status_change_can_skip_notification(): void
    {
        $admin = User::query()->where('email', 'admin@k-elec.co.ke')->firstOrFail();
        $claim = $this->makeClaim();

        $this->actingAs($admin)
            ->put(route('admin.claims.update', $claim), [
                'status' => ClaimStatus::Resolved->value,
                'admin_notes' => 'Resolved quietly.',
                'notify_customer' => '0',
            ])
            ->assertRedirect();

        $this->assertSame(0, NotificationLog::query()
            ->where('notification_type', 'claim_status_updated')
            ->count());
    }

    public function test_notes_only_update_does_not_notify(): void
    {
        $admin = User::query()->where('email', 'admin@k-elec.co.ke')->firstOrFail();
        $claim = $this->makeClaim();

        $this->actingAs($admin)
            ->put(route('admin.claims.update', $claim), [
                'status' => ClaimStatus::Submitted->value,
                'admin_notes' => 'Internal note only.',
                'notify_customer' => '1',
            ])
            ->assertRedirect();

        $this->assertSame(0, NotificationLog::query()
            ->where('notification_type', 'claim_status_updated')
            ->count());
    }

    protected function makeClaim(): WarrantyClaim
    {
        $customer = Customer::factory()->create([
            'email' => 'claimant@example.com',
            'mobile_number' => '0712345678',
            'mobile_normalized' => '254712345678',
        ]);

        $warranty = Warranty::factory()->create([
            'customer_id' => $customer->id,
            'status' => WarrantyStatus::Active,
        ]);

        return WarrantyClaim::factory()->create([
            'customer_id' => $customer->id,
            'warranty_id' => $warranty->id,
            'status' => ClaimStatus::Submitted,
            'subject' => 'Cooling issue',
            'description' => 'Unit not cooling properly.',
        ]);
    }
}
