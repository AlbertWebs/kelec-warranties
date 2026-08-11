<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Catalog barcodes were incorrectly stored as unit serials during Odoo sync.
        DB::table('products')
            ->whereNotNull('barcode')
            ->whereColumn('serial_number', 'barcode')
            ->update(['serial_number' => null]);

        DB::table('products')
            ->whereNotNull('default_code')
            ->whereColumn('serial_number', 'default_code')
            ->update(['serial_number' => null]);

        DB::table('products')
            ->whereNotNull('sku')
            ->whereColumn('serial_number', 'sku')
            ->update(['serial_number' => null]);

        DB::table('products')
            ->whereNotNull('model')
            ->whereColumn('serial_number', 'model')
            ->update(['serial_number' => null]);
    }

    public function down(): void
    {
        // Irreversible data cleanup.
    }
};
