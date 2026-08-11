<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warranty_rules', function (Blueprint $table) {
            $table->unsignedInteger('warranty_duration_months')->nullable()->default(null)->change();
        });

        Schema::table('product_categories', function (Blueprint $table) {
            $table->unsignedInteger('default_warranty_months')->nullable()->default(null)->change();
        });

        // Let Admin → Settings control the site-wide default instead of seeded 12-month overrides.
        DB::table('warranty_rules')
            ->whereNull('product_id')
            ->whereNull('product_category_id')
            ->update(['warranty_duration_months' => null]);

        DB::table('product_categories')
            ->where('default_warranty_months', 12)
            ->update(['default_warranty_months' => null]);
    }

    public function down(): void
    {
        Schema::table('warranty_rules', function (Blueprint $table) {
            $table->unsignedInteger('warranty_duration_months')->default(12)->nullable(false)->change();
        });

        Schema::table('product_categories', function (Blueprint $table) {
            $table->unsignedInteger('default_warranty_months')->default(12)->nullable(false)->change();
        });
    }
};
