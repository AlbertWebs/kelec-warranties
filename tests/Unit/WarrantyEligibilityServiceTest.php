<?php

namespace Tests\Unit;

use App\Services\WarrantyEligibilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarrantyEligibilityServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_eligible_warranty_starts_on_purchase_date(): void
    {
        $service = app(WarrantyEligibilityService::class);
        $purchaseDate = now()->subDays(10)->startOfDay();

        $result = $service->evaluate(
            product: null,
            purchaseDate: $purchaseDate,
            serialFound: true,
            hasActiveDuplicate: false,
        );

        $this->assertTrue($result['eligible']);
        $this->assertSame($purchaseDate->toDateString(), $result['start_date']?->toDateString());
        $this->assertSame('purchase_date', $result['start_date_method']);
        $this->assertNotSame(now()->toDateString(), $result['start_date']?->toDateString());
    }

    public function test_eligible_warranty_without_purchase_date_has_no_start_date(): void
    {
        $service = app(WarrantyEligibilityService::class);

        $result = $service->evaluate(
            product: null,
            purchaseDate: null,
            serialFound: true,
            hasActiveDuplicate: false,
        );

        $this->assertTrue($result['eligible']);
        $this->assertNull($result['start_date']);
        $this->assertNull($result['expiry_date']);
    }
}
