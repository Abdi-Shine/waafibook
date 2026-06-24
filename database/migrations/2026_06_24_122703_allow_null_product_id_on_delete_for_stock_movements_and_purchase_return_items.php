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
        //
        // The constraint name isn't assumed to follow Laravel's convention
        // (it doesn't on every environment this app runs in) — it's looked
        // up from information_schema instead.
        $this->dropProductIdForeignKey('purchase_return_items');
        DB::statement('ALTER TABLE purchase_return_items MODIFY product_id BIGINT UNSIGNED NULL');
        Schema::table('purchase_return_items', function (Blueprint $table) {
            $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');
        });

        $this->dropProductIdForeignKey('stock_movements');
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
        $this->dropProductIdForeignKey('purchase_return_items');
        DB::statement('ALTER TABLE purchase_return_items MODIFY product_id BIGINT UNSIGNED NOT NULL');
        Schema::table('purchase_return_items', function (Blueprint $table) {
            $table->foreign('product_id')->references('id')->on('products');
        });

        $this->dropProductIdForeignKey('stock_movements');
        DB::statement('ALTER TABLE stock_movements MODIFY product_id BIGINT UNSIGNED NOT NULL');
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreign('product_id')->references('id')->on('products');
        });
    }

    /**
     * Drop whatever the product_id -> products foreign key is actually
     * named on this table, if one exists at all.
     */
    private function dropProductIdForeignKey(string $table): void
    {
        $rows = DB::select(
            "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
               AND COLUMN_NAME = 'product_id' AND REFERENCED_TABLE_NAME = 'products'",
            [$table]
        );

        foreach ($rows as $row) {
            DB::statement("ALTER TABLE `$table` DROP FOREIGN KEY `{$row->CONSTRAINT_NAME}`");
        }
    }
};
