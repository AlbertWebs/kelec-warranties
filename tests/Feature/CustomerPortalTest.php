<?php

namespace Tests\Feature;

use App\Enums\WarrantyStatus;
use App\Models\Customer;
use App\Models\Warranty;
use App\Models\WarrantyClaim;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_register_and_login(): void
    {
        $this->post(route('customer.register.store'), [
            'full_name' => 'Jane Customer',
            'mobile_number' => '0712345678',
            'email' => 'jane@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(route('customer.warranties.index'));

        $this->assertAuthenticatedAs(
            Customer::query()->where('email', 'jane@example.com')->first(),
            'customer'
        );

        auth('customer')->logout();

        $this->post(route('customer.login.store'), [
            'login' => '0712345678',
            'password' => 'password',
        ])->assertRedirect(route('customer.warranties.index'));

        $this->assertAuthenticated('customer');
    }

    public function test_registering_links_existing_warranty_customer_without_password(): void
    {
        $customer = Customer::factory()->create([
            'email' => 'existing@example.com',
            'mobile_number' => '0798765432',
            'mobile_normalized' => '254798765432',
            'password' => null,
        ]);

        Warranty::factory()->create([
            'customer_id' => $customer->id,
            'status' => WarrantyStatus::Active,
            'reference' => 'KEL-WTY-2026-999001',
        ]);

        $this->post(route('customer.register.store'), [
            'full_name' => 'Existing Customer',
            'mobile_number' => '0798765432',
            'email' => 'existing@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(route('customer.warranties.index'));

        $this->assertSame(1, Customer::query()->count());
        $this->get(route('customer.warranties.index'))
            ->assertOk()
            ->assertSee('KEL-WTY-2026-999001');
    }

    public function test_warranty_history_is_scoped_to_authenticated_customer(): void
    {
        $customer = Customer::factory()->withPassword()->create();
        $other = Customer::factory()->withPassword()->create();

        $mine = Warranty::factory()->create([
            'customer_id' => $customer->id,
            'reference' => 'KEL-WTY-2026-111111',
        ]);
        Warranty::factory()->create([
            'customer_id' => $other->id,
            'reference' => 'KEL-WTY-2026-222222',
        ]);

        $this->actingAs($customer, 'customer')
            ->get(route('customer.warranties.index'))
            ->assertOk()
            ->assertSee('KEL-WTY-2026-111111')
            ->assertDontSee('KEL-WTY-2026-222222');

        $this->actingAs($customer, 'customer')
            ->get(route('customer.warranties.show', $mine))
            ->assertOk();

        $foreign = Warranty::query()->where('reference', 'KEL-WTY-2026-222222')->firstOrFail();
        $this->actingAs($customer, 'customer')
            ->get(route('customer.warranties.show', $foreign))
            ->assertNotFound();
    }

    public function test_customer_can_create_claim_against_own_active_warranty_only(): void
    {
        $customer = Customer::factory()->withPassword()->create();
        $other = Customer::factory()->withPassword()->create();

        $active = Warranty::factory()->create([
            'customer_id' => $customer->id,
            'status' => WarrantyStatus::Active,
        ]);
        $rejected = Warranty::factory()->create([
            'customer_id' => $customer->id,
            'status' => WarrantyStatus::Rejected,
        ]);
        $foreign = Warranty::factory()->create([
            'customer_id' => $other->id,
            'status' => WarrantyStatus::Active,
        ]);

        $this->actingAs($customer, 'customer')
            ->post(route('customer.claims.store'), [
                'warranty_id' => $active->id,
                'subject' => 'Not cooling',
                'description' => 'Fridge does not cool properly.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('warranty_claims', [
            'customer_id' => $customer->id,
            'warranty_id' => $active->id,
            'subject' => 'Not cooling',
        ]);

        $this->actingAs($customer, 'customer')
            ->from(route('customer.claims.create'))
            ->post(route('customer.claims.store'), [
                'warranty_id' => $rejected->id,
                'subject' => 'Should fail',
                'description' => 'Rejected warranty claim attempt.',
            ])
            ->assertSessionHasErrors('warranty_id');

        $this->actingAs($customer, 'customer')
            ->from(route('customer.claims.create'))
            ->post(route('customer.claims.store'), [
                'warranty_id' => $foreign->id,
                'subject' => 'Should fail',
                'description' => 'Foreign warranty claim attempt.',
            ])
            ->assertSessionHasErrors('warranty_id');

        $this->assertSame(1, WarrantyClaim::query()->count());
    }
}
