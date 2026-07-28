<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Seeder;

class TenProductsSeeder extends Seeder
{
    public function run(): void
    {
        $categories = ProductCategory::query()->pluck('id', 'name');
        $fallbackCategoryId = ProductCategory::query()->value('id');

        $products = [
            ['name' => 'K-Elec No Frost Fridge 320L', 'sku' => 'KE-FRD-320', 'model' => 'KFR-320NF', 'category' => 'Refrigerators'],
            ['name' => 'K-Elec Double Door Fridge 500L', 'sku' => 'KE-FRD-500', 'model' => 'KFR-500DD', 'category' => 'Refrigerators'],
            ['name' => 'K-Elec Front Load Washing Machine 8KG', 'sku' => 'KE-WM-8FL', 'model' => 'KWM-8FL', 'category' => 'Washing Machines'],
            ['name' => 'K-Elec Top Load Washing Machine 10KG', 'sku' => 'KE-WM-10TL', 'model' => 'KWM-10TL', 'category' => 'Washing Machines'],
            ['name' => 'K-Elec Smart TV 55 Inch 4K', 'sku' => 'KE-TV-55UHD', 'model' => 'KTV-55UHD', 'category' => 'Televisions'],
            ['name' => 'K-Elec Smart TV 43 Inch FHD', 'sku' => 'KE-TV-43FHD', 'model' => 'KTV-43FHD', 'category' => 'Televisions'],
            ['name' => 'K-Elec Upright Freezer 200L', 'sku' => 'KE-FRZ-200', 'model' => 'KFRZ-200UP', 'category' => 'Freezers'],
            ['name' => 'K-Elec Microwave Oven 30L', 'sku' => 'KE-MWO-30', 'model' => 'KMWO-30D', 'category' => 'Microwaves'],
            ['name' => 'K-Elec Built-in Oven 60CM', 'sku' => 'KE-OVN-60B', 'model' => 'KOV-60B', 'category' => 'Ovens'],
            ['name' => 'K-Elec Chest Freezer 350L', 'sku' => 'KE-FRZ-350', 'model' => 'KFRZ-350CH', 'category' => 'Freezers'],
        ];

        foreach ($products as $index => $item) {
            Product::updateOrCreate(
                ['sku' => $item['sku']],
                [
                    'name' => $item['name'],
                    'display_name' => $item['name'],
                    'model' => $item['model'],
                    'brand' => 'K-Elec',
                    'brand_name' => 'K-Elec',
                    'default_code' => $item['sku'],
                    'product_code' => $item['sku'],
                    'barcode' => 'KE'.str_pad((string) ($index + 1), 10, '0', STR_PAD_LEFT),
                    'serial_number' => $item['sku'].'-SERIAL',
                    'product_category_id' => $categories[$item['category']] ?? $fallbackCategoryId,
                    'category_name' => $item['category'],
                    'default_warranty_months' => 12,
                    'registration_grace_days' => 30,
                    'is_active' => true,
                    'active' => true,
                    'sale_ok' => true,
                    'purchase_ok' => false,
                    'tracking' => 'serial',
                    'serial_tracking_enabled' => true,
                    'manual_verification_allowed' => true,
                    'receipt_required' => false,
                    'sync_status' => 'seeded',
                    'last_synced_at' => now(),
                    'is_odoo_managed' => false,
                ]
            );
        }
    }
}

