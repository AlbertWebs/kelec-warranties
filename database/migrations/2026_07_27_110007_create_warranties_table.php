<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warranties', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('purchase_source_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('dealer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name')->nullable();
            $table->string('product_model')->nullable();
            $table->string('serial_number')->index();
            $table->string('branch_name')->nullable();
            $table->date('purchase_date')->nullable();
            $table->timestamp('registration_date')->nullable();
            $table->date('warranty_start_date')->nullable();
            $table->date('warranty_expiry_date')->nullable()->index();
            $table->unsignedInteger('warranty_duration_months')->nullable();
            $table->string('status')->default('submitted')->index();
            $table->string('eligibility_result')->nullable();
            $table->string('odoo_customer_id')->nullable()->index();
            $table->string('odoo_product_id')->nullable()->index();
            $table->string('odoo_serial_id')->nullable()->index();
            $table->string('odoo_pos_order_id')->nullable()->index();
            $table->string('odoo_sales_order_id')->nullable()->index();
            $table->string('odoo_invoice_id')->nullable()->index();
            $table->string('invoice_number')->nullable()->index();
            $table->string('receipt_path')->nullable();
            $table->string('receipt_original_name')->nullable();
            $table->string('registration_source')->default('public_portal')->index();
            $table->boolean('marketing_consent')->default(false);
            $table->boolean('privacy_accepted')->default(false);
            $table->timestamp('consent_timestamp')->nullable();
            $table->string('consent_source')->nullable();
            $table->string('consent_ip')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('internal_notes')->nullable();
            $table->text('customer_notes')->nullable();
            $table->boolean('odoo_validated')->nullable();
            $table->text('odoo_validation_message')->nullable();
            $table->boolean('requires_manual_verification')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['serial_number', 'status']);
            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warranties');
    }
};
