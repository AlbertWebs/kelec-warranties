<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warranty_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warranty_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('consent_type');
            $table->boolean('granted')->default(false);
            $table->string('source')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('consented_at')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'consent_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warranty_consents');
    }
};
