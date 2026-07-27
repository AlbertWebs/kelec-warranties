<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('product_code')->nullable()->index();
            $table->string('sku')->nullable()->unique();
            $table->string('model')->nullable()->index();
            $table->string('brand')->default('K-Elec');
            $table->unsignedInteger('default_warranty_months')->nullable();
            $table->unsignedInteger('registration_grace_days')->default(30);
            $table->string('odoo_product_id')->nullable()->index();
            $table->boolean('is_active')->default(true);
            $table->boolean('serial_tracking_enabled')->default(true);
            $table->boolean('manual_verification_allowed')->default(true);
            $table->boolean('receipt_required')->default(false);
            $table->boolean('is_odoo_managed')->default(false);
            $table->text('warranty_terms')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
