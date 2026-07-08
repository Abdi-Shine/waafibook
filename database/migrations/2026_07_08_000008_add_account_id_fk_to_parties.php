<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Nullify dangling account_id values before adding the FK.
        // Note: account_type and account_code remain — they are actively
        // written by CustomerController and SupplierController as a cache.
        foreach (['customers', 'suppliers'] as $table) {
            DB::statement("UPDATE `{$table}` SET account_id = NULL
                WHERE account_id IS NOT NULL
                AND account_id NOT IN (SELECT id FROM chart_of_accounts)");

            Schema::table($table, function (Blueprint $t) {
                $t->index('account_id');
                $t->foreign('account_id')
                  ->references('id')->on('chart_of_accounts')
                  ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (['customers', 'suppliers'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropForeign(['account_id']);
                $t->dropIndex(['account_id']);
            });
        }
    }
};
