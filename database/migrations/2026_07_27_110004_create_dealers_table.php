<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dealers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('dealer_code')->nullable()->unique();
            $table->string('contact_person')->nullable();
            $table->string('mobile_number')->nullable()->index();
            $table->string('email')->nullable();
            $table->string('county')->nullable();
            $table->string('town')->nullable();
            $table->string('physical_location')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_authorised')->default(true);
            $table->string('odoo_partner_id')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dealers');
    }
};
