<?php

namespace Tests\Feature;

use App\Enums\WarrantyStatus;
use App\Models\Customer;
use App\Models\User;
use App\Models\Warranty;
use App\Models\WarrantyClaim;
use App\Models\WarrantyClaimPhoto;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ClaimPhotoUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_claim_form_includes_photo_upload(): void
    {
        $customer = Customer::factory()->withPassword()->create();
        Warranty::factory()->create([
            'customer_id' => $customer->id,
            'status' => WarrantyStatus::Active,
        ]);

        $this->actingAs($customer, 'customer')
            ->get(route('customer.claims.create'))
            ->assertOk()
            ->assertSee('Photos of the issue')
            ->assertSee('name="photos[]"', false);
    }

    public function test_customer_can_upload_photos_when_filing_a_claim(): void
    {
        Storage::fake('local');

        $customer = Customer::factory()->withPassword()->create();
        $warranty = Warranty::factory()->create([
            'customer_id' => $customer->id,
            'status' => WarrantyStatus::Active,
        ]);

        $this->actingAs($customer, 'customer')
            ->post(route('customer.claims.store'), [
                'warranty_id' => $warranty->id,
                'subject' => 'Not cooling',
                'description' => 'Fridge does not cool properly.',
                'photos' => [
                    UploadedFile::fake()->image('fault-front.jpg', 400, 300),
                    UploadedFile::fake()->image('fault-side.png', 400, 300),
                ],
            ])
            ->assertRedirect();

        $claim = WarrantyClaim::query()->firstOrFail();
        $this->assertSame(2, $claim->photos()->count());

        foreach ($claim->photos as $photo) {
            Storage::disk('local')->assertExists($photo->path);
        }

        $this->actingAs($customer, 'customer')
            ->get(route('customer.claims.show', $claim))
            ->assertOk()
            ->assertSee('fault-front.jpg')
            ->assertSee('fault-side.png');

        $photo = $claim->photos->first();
        $this->actingAs($customer, 'customer')
            ->get(route('customer.claims.photos.show', [$claim, $photo]))
            ->assertOk();
    }

    public function test_customer_claim_rejects_non_image_uploads(): void
    {
        Storage::fake('local');

        $customer = Customer::factory()->withPassword()->create();
        $warranty = Warranty::factory()->create([
            'customer_id' => $customer->id,
            'status' => WarrantyStatus::Active,
        ]);

        $this->actingAs($customer, 'customer')
            ->from(route('customer.claims.create'))
            ->post(route('customer.claims.store'), [
                'warranty_id' => $warranty->id,
                'subject' => 'Not cooling',
                'description' => 'Fridge does not cool properly.',
                'photos' => [
                    UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf'),
                ],
            ])
            ->assertRedirect(route('customer.claims.create'))
            ->assertSessionHasErrors('photos.0');

        $this->assertSame(0, WarrantyClaim::query()->count());
        $this->assertSame(0, WarrantyClaimPhoto::query()->count());
    }

    public function test_guest_can_upload_photos_when_filing_a_public_claim(): void
    {
        Storage::fake('local');

        $customer = Customer::factory()->create([
            'mobile_number' => '0712345678',
            'mobile_normalized' => '254712345678',
        ]);

        $warranty = Warranty::factory()->create([
            'customer_id' => $customer->id,
            'serial_number' => 'SNPHOTO001',
            'status' => WarrantyStatus::Active,
        ]);

        $this->post(route('warranty.claim.verify'), [
            'serial_number' => 'SNPHOTO001',
            'mobile_number' => '0712345678',
        ])->assertRedirect(route('warranty.hub', ['tab' => 'claim']));

        $this->get(route('warranty.hub', ['tab' => 'claim']))
            ->assertOk()
            ->assertSee('Photos of the issue')
            ->assertSee('name="photos[]"', false);

        $this->post(route('warranty.claim.store'), [
            'subject' => 'Power issue',
            'description' => 'Unit will not turn on.',
            'photos' => [
                UploadedFile::fake()->image('serial-plate.jpg', 400, 300),
            ],
        ])
            ->assertRedirect(route('warranty.hub', ['tab' => 'claim']))
            ->assertSessionHas('submitted_claim_reference');

        $claim = WarrantyClaim::query()->firstOrFail();
        $this->assertSame(1, $claim->photos()->count());
        Storage::disk('local')->assertExists($claim->photos->first()->path);

        $this->get(route('warranty.hub', ['tab' => 'claim']))
            ->assertOk()
            ->assertSee('Photos uploaded')
            ->assertSee('1');
    }

    public function test_other_customer_cannot_view_claim_photos(): void
    {
        Storage::fake('local');

        $customer = Customer::factory()->withPassword()->create();
        $other = Customer::factory()->withPassword()->create();
        $warranty = Warranty::factory()->create([
            'customer_id' => $customer->id,
            'status' => WarrantyStatus::Active,
        ]);

        $this->actingAs($customer, 'customer')
            ->post(route('customer.claims.store'), [
                'warranty_id' => $warranty->id,
                'subject' => 'Not cooling',
                'description' => 'Fridge does not cool properly.',
                'photos' => [
                    UploadedFile::fake()->image('fault.jpg', 200, 200),
                ],
            ])
            ->assertRedirect();

        $claim = WarrantyClaim::query()->firstOrFail();
        $photo = $claim->photos()->firstOrFail();

        $this->actingAs($other, 'customer')
            ->get(route('customer.claims.photos.show', [$claim, $photo]))
            ->assertNotFound();
    }

    public function test_admin_can_view_uploaded_claim_photos(): void
    {
        Storage::fake('local');
        $this->seed(DatabaseSeeder::class);

        $customer = Customer::factory()->withPassword()->create();
        $warranty = Warranty::factory()->create([
            'customer_id' => $customer->id,
            'status' => WarrantyStatus::Active,
        ]);

        $this->actingAs($customer, 'customer')
            ->post(route('customer.claims.store'), [
                'warranty_id' => $warranty->id,
                'subject' => 'Not cooling',
                'description' => 'Fridge does not cool properly.',
                'photos' => [
                    UploadedFile::fake()->image('admin-view.jpg', 200, 200),
                ],
            ])
            ->assertRedirect();

        $claim = WarrantyClaim::query()->firstOrFail();
        $photo = $claim->photos()->firstOrFail();
        $admin = User::query()->where('email', 'admin@k-elec.co.ke')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.claims.show', $claim))
            ->assertOk()
            ->assertSee('admin-view.jpg');

        $this->actingAs($admin)
            ->get(route('admin.claims.photos.show', [$claim, $photo]))
            ->assertOk();
    }
}
