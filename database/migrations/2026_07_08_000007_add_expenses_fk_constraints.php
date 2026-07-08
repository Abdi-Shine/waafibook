<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Nullify orphaned IDs before adding FK constraints
        DB::statement("UPDATE expenses SET expense_account_id = NULL
            WHERE expense_account_id IS NOT NULL
            AND expense_account_id NOT IN (SELECT id FROM chart_of_accounts)");

        DB::statement("UPDATE expenses SET bank_account_id = NULL
            WHERE bank_account_id IS NOT NULL
            AND bank_account_id NOT IN (SELECT id FROM chart_of_accounts)");

        DB::statement("UPDATE expenses SET branch_id = NULL
            WHERE branch_id IS NOT NULL
            AND branch_id NOT IN (SELECT id FROM branches)");

        DB::statement("UPDATE expenses SET created_by = NULL
            WHERE created_by IS NOT NULL
            AND created_by NOT IN (SELECT id FROM users)");

        Schema::table('expenses', function (Blueprint $table) {
            $table->foreign('expense_account_id')
                  ->references('id')->on('chart_of_accounts')
                  ->nullOnDelete();

            $table->foreign('bank_account_id')
                  ->references('id')->on('chart_of_accounts')
                  ->nullOnDelete();

            $table->foreign('branch_id')
                  ->references('id')->on('branches')
                  ->nullOnDelete();

            $table->foreign('created_by')
                  ->references('id')->on('users')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['expense_account_id']);
            $table->dropForeign(['bank_account_id']);
            $table->dropForeign(['branch_id']);
            $table->dropForeign(['created_by']);
        });
    }
};
