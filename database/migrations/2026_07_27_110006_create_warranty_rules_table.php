<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warranty_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_category_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('warranty_duration_months')->default(12);
            $table->unsignedInteger('registration_grace_days')->default(30);
            $table->json('eligible_purchase_sources')->nullable();
            $table->boolean('receipt_required')->default(false);
            $table->boolean('serial_validation_mandatory')->default(false);
            $table->boolean('manual_verification_allowed')->default(true);
            $table->string('start_date_method')->default('purchase_date');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warranty_rules');
    }
};
