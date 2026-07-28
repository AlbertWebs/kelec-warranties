<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('odoo_id')->nullable()->after('odoo_product_id');
            $table->string('product_template_id')->nullable()->after('odoo_id');
            $table->string('display_name')->nullable()->after('name');
            $table->string('default_code')->nullable()->after('product_code');
            $table->string('barcode')->nullable()->after('default_code');
            $table->string('serial_number')->nullable()->after('barcode');
            $table->string('product_type')->nullable()->after('serial_number');
            $table->string('category_id')->nullable()->after('product_type');
            $table->string('category_name')->nullable()->after('category_id');
            $table->string('brand_id')->nullable()->after('category_name');
            $table->string('brand_name')->nullable()->after('brand_id');
            $table->text('description')->nullable()->after('warranty_terms');
            $table->text('description_sale')->nullable()->after('description');
            $table->decimal('list_price', 14, 2)->nullable()->after('description_sale');
            $table->decimal('standard_price', 14, 2)->nullable()->after('list_price');
            $table->string('currency', 20)->nullable()->after('standard_price');
            $table->string('uom_id')->nullable()->after('currency');
            $table->string('uom_name')->nullable()->after('uom_id');
            $table->boolean('active')->default(true)->after('uom_name');
            $table->boolean('sale_ok')->default(true)->after('active');
            $table->boolean('purchase_ok')->default(false)->after('sale_ok');
            $table->string('tracking')->nullable()->after('purchase_ok');
            $table->text('image_url')->nullable()->after('tracking');
            $table->timestamp('odoo_created_at')->nullable()->after('image_url');
            $table->timestamp('odoo_updated_at')->nullable()->after('odoo_created_at');
            $table->timestamp('last_synced_at')->nullable()->after('odoo_updated_at');
            $table->string('sync_status')->default('pending')->after('last_synced_at');
            $table->json('raw_odoo_data')->nullable()->after('sync_status');

            $table->unique('odoo_id');
            $table->unique('default_code');
            $table->unique('barcode');
            $table->index('serial_number');
            $table->index('sync_status');
            $table->index(['active', 'sale_ok']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['odoo_id']);
            $table->dropUnique(['default_code']);
            $table->dropUnique(['barcode']);
            $table->dropIndex(['serial_number']);
            $table->dropIndex(['sync_status']);
            $table->dropIndex(['active', 'sale_ok']);

            $table->dropColumn([
                'odoo_id',
                'product_template_id',
                'display_name',
                'default_code',
                'barcode',
                'serial_number',
                'product_type',
                'category_id',
                'category_name',
                'brand_id',
                'brand_name',
                'description',
                'description_sale',
                'list_price',
                'standard_price',
                'currency',
                'uom_id',
                'uom_name',
                'active',
                'sale_ok',
                'purchase_ok',
                'tracking',
                'image_url',
                'odoo_created_at',
                'odoo_updated_at',
                'last_synced_at',
                'sync_status',
                'raw_odoo_data',
            ]);
        });
    }
};

