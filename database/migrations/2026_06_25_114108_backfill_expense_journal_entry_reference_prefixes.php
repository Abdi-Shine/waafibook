<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // PurchaseExpense and the general Expense model both used a plain
        // "EXP-<id>" journal reference, with no way to tell which model a
        // given id belonged to — two different expenses (one of each type)
        // could collide on the same reference. Code now writes/looks up
        // "EXP-PE-<id>" / "EXP-GE-<id>" instead; this backfills any rows
        // still using the old bare format so existing expenses can still be
        // edited/deleted correctly afterward, instead of becoming orphaned.
        // The entry's own description text reliably tells the two apart:
        // PurchaseExpense writes "Purchase Expense: ...", Expense writes
        // plain "Expense: ...".
        DB::table('journal_entries')
            ->where('reference', 'like', 'EXP-%')
            ->where('reference', 'not like', 'EXP-PE-%')
            ->where('reference', 'not like', 'EXP-GE-%')
            ->where('description', 'like', 'Purchase Expense:%')
            ->update(['reference' => DB::raw("CONCAT('EXP-PE-', SUBSTRING(reference, 5))")]);

        DB::table('journal_entries')
            ->where('reference', 'like', 'EXP-%')
            ->where('reference', 'not like', 'EXP-PE-%')
            ->where('reference', 'not like', 'EXP-GE-%')
            ->update(['reference' => DB::raw("CONCAT('EXP-GE-', SUBSTRING(reference, 5))")]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('journal_entries')
            ->where('reference', 'like', 'EXP-PE-%')
            ->update(['reference' => DB::raw("CONCAT('EXP-', SUBSTRING(reference, 8))")]);

        DB::table('journal_entries')
            ->where('reference', 'like', 'EXP-GE-%')
            ->update(['reference' => DB::raw("CONCAT('EXP-', SUBSTRING(reference, 8))")]);
    }
};
