<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // products.unit is a free-text string (default 'Piece') — not a FK to
        // the units table, so reports can fragment on capitalisation differences.
        // Add the FK column alongside the string column and backfill it by
        // matching units.name per company. The old 'unit' string column is kept
        // so existing Blade/controller code continues to work; it can be dropped
        // in a later migration once code is updated.
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('unit_id')->nullable()->after('unit');
            $table->index('unit_id');
        });

        // Backfill unit_id where units.name matches products.unit (case-insensitive)
        // and they belong to the same company.
        DB::statement("UPDATE products p
            JOIN units u ON LOWER(u.name) = LOWER(p.unit)
                         AND u.company_id = p.company_id
            SET p.unit_id = u.id
            WHERE p.unit_id IS NULL");

        Schema::table('products', function (Blueprint $table) {
            $table->foreign('unit_id')
                  ->references('id')->on('units')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['unit_id']);
            $table->dropIndex(['unit_id']);
            $table->dropColumn('unit_id');
        });
    }
};
