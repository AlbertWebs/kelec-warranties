<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Models\Warranty;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarrantyDisplayFieldsTest extends TestCase
{
    use RefreshDatabase;

    public function test_certificate_fields_prefer_stored_warranty_product_details(): void
    {
        $product = Product::factory()->create([
            'name' => '43FL990KSB',
            'model' => '43FL990KSB',
        ]);

        $warranty = Warranty::factory()->create([
            'product_id' => $product->id,
            'product_name' => '50UL990KSB',
            'product_model' => '50UL990KSB',
        ]);

        $this->assertSame('50UL990KSB', $warranty->displayProductName());
        $this->assertSame('50UL990KSB', $warranty->displayModel());
    }

    public function test_display_fields_fall_back_to_linked_product_when_warranty_fields_empty(): void
    {
        $product = Product::factory()->create([
            'name' => '43FL990KSB',
            'display_name' => null,
            'model' => '43FL990KSB',
        ]);

        $warranty = Warranty::factory()->create([
            'product_id' => $product->id,
            'product_name' => '',
            'product_model' => '',
        ]);

        $this->assertSame('43FL990KSB', $warranty->displayProductName());
        $this->assertSame('43FL990KSB', $warranty->displayModel());
    }
}
