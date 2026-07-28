<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('odoo_product_sync_runs', function (Blueprint $table) {
            $table->id();
            $table->string('sync_type')->default('full')->index();
            $table->string('status')->default('pending')->index();
            $table->foreignId('started_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('total_records')->default(0);
            $table->unsignedInteger('processed_records')->default(0);
            $table->unsignedInteger('created_records')->default(0);
            $table->unsignedInteger('updated_records')->default(0);
            $table->unsignedInteger('failed_records')->default(0);
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('odoo_product_sync_failures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sync_run_id')->constrained('odoo_product_sync_runs')->cascadeOnDelete();
            $table->string('external_id')->nullable()->index();
            $table->string('identifier')->nullable()->index();
            $table->text('error_message')->nullable();
            $table->unsignedInteger('retry_count')->default(0);
            $table->string('status')->default('pending')->index();
            $table->json('payload')->nullable();
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('odoo_product_sync_failures');
        Schema::dropIfExists('odoo_product_sync_runs');
    }
};

