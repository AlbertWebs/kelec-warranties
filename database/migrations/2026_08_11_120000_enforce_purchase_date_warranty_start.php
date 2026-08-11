<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('warranty_rules')
            ->where('start_date_method', 'registration_date')
            ->update(['start_date_method' => 'purchase_date']);
    }

    public function down(): void
    {
        // No rollback — purchase date is the intended business rule.
    }
};
