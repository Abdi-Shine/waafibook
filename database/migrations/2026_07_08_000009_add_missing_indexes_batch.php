<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // announcements, sales_returns, sales_return_items, branch_transfers,
        // shareholders already have company_id indexes from prior migrations.
        // Only the stock_movements composite (product_id, branch_id) is missing —
        // MySQL uses the leftmost prefix, so product-only lookups hit this index too.
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->index(['product_id', 'branch_id']);
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', fn($t) => $t->dropIndex(['product_id', 'branch_id']));
    }
};
