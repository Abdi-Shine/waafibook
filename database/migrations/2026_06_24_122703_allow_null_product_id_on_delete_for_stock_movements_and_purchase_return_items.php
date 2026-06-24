<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Both tables' product_id FK had no onDelete action (defaults to
        // RESTRICT), so deleting a product with any return/stock-movement
        // history threw a raw SQL integrity-constraint error. Switch to
        // SET NULL, matching the convention already used by
        // purchase_bill_items/sales_order_items/purchase_order_items —
        // keep the historical record, just unlink the deleted product.
        Schema::table('purchase_return_items', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
        });
        DB::statement('ALTER TABLE purchase_return_items MODIFY product_id BIGINT UNSIGNED NULL');
        Schema::table('purchase_return_items', function (Blueprint $table) {
            $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
        });
        DB::statement('ALTER TABLE stock_movements MODIFY product_id BIGINT UNSIGNED NULL');
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_return_items', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
        });
        DB::statement('ALTER TABLE purchase_return_items MODIFY product_id BIGINT UNSIGNED NOT NULL');
        Schema::table('purchase_return_items', function (Blueprint $table) {
            $table->foreign('product_id')->references('id')->on('products');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
        });
        DB::statement('ALTER TABLE stock_movements MODIFY product_id BIGINT UNSIGNED NOT NULL');
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreign('product_id')->references('id')->on('products');
        });
    }
};
