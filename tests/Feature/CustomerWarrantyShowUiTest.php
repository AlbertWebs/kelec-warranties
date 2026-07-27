<?php

namespace Tests\Feature;

use App\Enums\WarrantyStatus;
use App\Models\Customer;
use App\Models\Warranty;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerWarrantyShowUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_active_warranty_show_has_file_claim_button(): void
    {
        $customer = Customer::factory()->create([
            'password' => 'password',
        ]);

        $warranty = Warranty::factory()->create([
            'customer_id' => $customer->id,
            'status' => WarrantyStatus::Active,
        ]);

        $this->actingAs($customer, 'customer')
            ->get(route('customer.warranties.show', $warranty))
            ->assertOk()
            ->assertSee('File a claim')
            ->assertSee(route('customer.claims.create', ['warranty_id' => $warranty->id], false), false);
    }
}