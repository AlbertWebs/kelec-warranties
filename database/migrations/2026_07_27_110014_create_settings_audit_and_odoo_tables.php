<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('group')->default('general')->index();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->boolean('is_encrypted')->default(false);
            $table->string('type')->default('string');
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('entity_type')->nullable()->index();
            $table->unsignedBigInteger('entity_id')->nullable()->index();
            $table->json('previous_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['created_at']);
        });

        Schema::create('odoo_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->string('endpoint')->nullable();
            $table->string('action');
            $table->string('request_reference')->nullable()->index();
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedInteger('retry_count')->default(0);
            $table->string('status')->default('pending')->index();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('odoo_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type');
            $table->unsignedBigInteger('local_id');
            $table->string('odoo_id')->index();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['entity_type', 'local_id']);
            $table->unique(['entity_type', 'odoo_id']);
        });

        Schema::create('integration_failures', function (Blueprint $table) {
            $table->id();
            $table->string('integration')->default('odoo');
            $table->string('action');
            $table->foreignId('warranty_id')->nullable()->constrained()->nullOnDelete();
            $table->text('error_message')->nullable();
            $table->unsignedInteger('retry_count')->default(0);
            $table->string('status')->default('pending')->index();
            $table->timestamp('next_retry_at')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_failures');
        Schema::dropIfExists('odoo_mappings');
        Schema::dropIfExists('odoo_sync_logs');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('system_settings');
    }
};
