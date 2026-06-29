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
        // chart_of_accounts cascades on company deletion, but journal_items
        // only RESTRICTed on account_id — so deleting a company failed with
        // "Cannot delete or update a parent row" the moment MySQL tried to
        // cascade-delete an account that still had journal items pointing
        // at it (even though those items were about to be cascade-deleted
        // themselves via their own company_id FK).
        Schema::table('journal_items', function (Blueprint $table) {
            $table->dropForeign('journal_items_account_id_foreign');
        });
        DB::statement('ALTER TABLE journal_items ADD CONSTRAINT journal_items_account_id_foreign FOREIGN KEY (account_id) REFERENCES chart_of_accounts(id) ON DELETE CASCADE');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('journal_items', function (Blueprint $table) {
            $table->dropForeign('journal_items_account_id_foreign');
        });
        DB::statement('ALTER TABLE journal_items ADD CONSTRAINT journal_items_account_id_foreign FOREIGN KEY (account_id) REFERENCES chart_of_accounts(id)');
    }
};
