<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();
            $table->string('mobile', 20);
            $table->text('message');
            $table->string('status', 30)->default('pending');
            $table->string('provider_message_id')->nullable()->index();
            $table->string('network_id', 20)->nullable();
            $table->unsignedInteger('response_code')->nullable();
            $table->string('response_description')->nullable();
            $table->text('provider_response')->nullable();
            $table->string('shortcode', 50)->nullable();
            $table->string('context', 100)->nullable()->index();
            $table->foreignId('notification_log_id')->nullable()->constrained('notification_logs')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_logs');
    }
};
