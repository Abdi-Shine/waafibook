<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // company_id was added to these tables via ALTER using unsignedBigInteger.
    // purchase_orders, purchase_order_items, purchase_bill_items already had
    // the FK added by a direct DB migration — only these three still need it.
    // Also fixes purchase_orders.branch_id (no FK).
    private array $tables = [
        'purchase_expenses',
        'purchase_returns',
        'purchase_return_items',
    ];

    public function up(): void
    {
        // Nullify orphaned company_id values so the FK addition doesn't fail.
        foreach ($this->tables as $table) {
            DB::statement("UPDATE `{$table}` SET company_id = NULL
                WHERE company_id IS NOT NULL
                AND company_id NOT IN (SELECT id FROM companies)");
        }

        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->foreign('company_id')
                  ->references('id')->on('companies')
                  ->cascadeOnDelete();
            });
        }

        // purchase_orders.branch_id also needs a FK (was also bare unsignedBigInteger)
        DB::statement("UPDATE purchase_orders SET branch_id = NULL
            WHERE branch_id IS NOT NULL
            AND branch_id NOT IN (SELECT id FROM branches)");

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->foreign('branch_id')
                  ->references('id')->on('branches')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
        });

        foreach (array_reverse($this->tables) as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropForeign(['company_id']);
            });
        }
    }
};
