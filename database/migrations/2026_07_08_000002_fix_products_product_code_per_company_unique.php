<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Check for cross-tenant product_code collisions before dropping the
        // global unique constraint. If duplicates exist across companies the
        // new per-company unique will still be added — the duplicates are fine.
        $duplicates = DB::select("
            SELECT product_code, COUNT(*) as cnt
            FROM products
            GROUP BY product_code
            HAVING cnt > 1
        ");

        if (!empty($duplicates)) {
            \Illuminate\Support\Facades\Log::info(
                'Schema migration 000002: cross-tenant product_code duplicates exist (expected for multi-tenant). Proceeding.',
                ['count' => count($duplicates)]
            );
        }

        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['product_code']);
            $table->unique(['company_id', 'product_code']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['company_id', 'product_code']);
            $table->unique(['product_code']);
        });
    }
};
