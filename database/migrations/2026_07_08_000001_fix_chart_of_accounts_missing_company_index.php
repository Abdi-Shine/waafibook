<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The 2026-03-26 index migration targeted 'accounts' (wrong name).
        // chart_of_accounts.company_id has never had an index — every tenant
        // query on the GL does a full table scan.
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->index('company_id');
            $table->index('parent_id'); // tree traversal queries
        });
    }

    public function down(): void
    {
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->dropIndex(['company_id']);
            $table->dropIndex(['parent_id']);
        });
    }
};
