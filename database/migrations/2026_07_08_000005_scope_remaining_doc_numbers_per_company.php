<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Five document number columns missed by the June 2026 scoping migration.
    // All are globally unique today — changed to (company_id, column) unique.
    private array $targets = [
        ['purchase_orders',  'po_number'],
        ['purchase_returns', 'return_number'],
        ['sales_returns',    'credit_note_no'],
        ['journal_entries',  'entry_number'],
        ['branch_transfers', 'transfer_no'],
    ];

    public function up(): void
    {
        foreach ($this->targets as [$table, $col]) {
            Schema::table($table, function (Blueprint $t) use ($col) {
                $t->dropUnique([$col]);
                $t->unique(['company_id', $col]);
            });
        }
    }

    public function down(): void
    {
        foreach ($this->targets as [$table, $col]) {
            Schema::table($table, function (Blueprint $t) use ($col) {
                $t->dropUnique(['company_id', $col]);
                $t->unique([$col]);
            });
        }
    }
};
