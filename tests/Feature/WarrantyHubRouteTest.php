<?php

namespace Tests\Feature;

use App\Enums\WarrantyStatus;
use App\Models\Customer;
use App\Models\Warranty;
use App\Models\WarrantyClaim;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarrantyHubRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_claim_tab_is_public_and_does_not_require_authentication(): void
    {
        $this->get(route('warranty.hub', ['tab' => 'claim']))
            ->assertOk()
            ->assertSee('File a warranty claim')
            ->assertSee('No account login required');
    }

    public function test_get_claim_verify_redirects_to_claim_form(): void
    {
        $this->get('/warranty/claim/verify')
            ->assertRedirect(route('warranty.hub', ['tab' => 'claim']));
    }

    public function test_warranty_hub_lookup_tab_redirects_to_lookup(): void
    {
        $this->get(route('warranty.hub', ['tab' => 'lookup']))
            ->assertRedirect(route('warranty.lookup'));
    }

    public function test_warranty_hub_default_redirects_to_register(): void
    {
        $this->get(route('warranty.hub'))
            ->assertRedirect(route('register-warranty.create'));
    }

    public function test_guest_can_verify_active_warranty_and_submit_claim(): void
    {
        $customer = Customer::factory()->create([
            'mobile_number' => '0712345678',
            'mobile_normalized' => '254712345678',
        ]);

        $warranty = Warranty::factory()->create([
            'customer_id' => $customer->id,
            'serial_number' => 'SNCLAIM001',
            'status' => WarrantyStatus::Active,
        ]);

        $this->post(route('warranty.claim.verify'), [
            'serial_number' => 'SNCLAIM001',
            'mobile_number' => '0712345678',
        ])->assertRedirect(route('warranty.hub', ['tab' => 'claim']));

        $this->get(route('warranty.hub', ['tab' => 'claim']))
            ->assertOk()
            ->assertSee('Describe the issue')
            ->assertSee($warranty->reference);

        $this->post(route('warranty.claim.store'), [
            'subject' => 'Not cooling',
            'description' => 'Fridge stopped cooling yesterday.',
        ])
            ->assertRedirect(route('warranty.hub', ['tab' => 'claim']))
            ->assertSessionHas('submitted_claim_reference');

        $this->get(route('warranty.hub', ['tab' => 'claim']))
            ->assertOk()
            ->assertSee('Claim submitted')
            ->assertSee('CLM-');

        $this->assertDatabaseHas('warranty_claims', [
            'customer_id' => $customer->id,
            'warranty_id' => $warranty->id,
            'subject' => 'Not cooling',
        ]);

        $this->assertSame(1, WarrantyClaim::query()->count());
    }

    public function test_guest_cannot_claim_inactive_warranty(): void
    {
        $customer = Customer::factory()->create([
            'mobile_number' => '0712345678',
            'mobile_normalized' => '254712345678',
        ]);

        Warranty::factory()->create([
            'customer_id' => $customer->id,
            'serial_number' => 'SNCLAIM002',
            'status' => WarrantyStatus::Expired,
        ]);

        $this->from(route('warranty.hub', ['tab' => 'claim']))
            ->post(route('warranty.claim.verify'), [
                'serial_number' => 'SNCLAIM002',
                'mobile_number' => '0712345678',
            ])
            ->assertSessionHasErrors('serial_number');

        $this->assertSame(0, WarrantyClaim::query()->count());
    }

    public function test_guest_cannot_verify_with_wrong_mobile(): void
    {
        $customer = Customer::factory()->create([
            'mobile_number' => '0712345678',
            'mobile_normalized' => '254712345678',
        ]);

        Warranty::factory()->create([
            'customer_id' => $customer->id,
            'serial_number' => 'SNCLAIM003',
            'status' => WarrantyStatus::Active,
        ]);

        $this->from(route('warranty.hub', ['tab' => 'claim']))
            ->post(route('warranty.claim.verify'), [
                'serial_number' => 'SNCLAIM003',
                'mobile_number' => '0799999999',
            ])
            ->assertSessionHasErrors('serial_number');
    }
}
