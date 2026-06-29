<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payment_ins', function (Blueprint $table) {
            // 'receipt' = money received from a customer (reduces what they
            // owe). 'refund' = money paid back out to a customer (settles a
            // Customer Refunds Payable balance from a return on an
            // already-paid invoice) — opposite direction on both the
            // customer balance and the journal entry.
            $table->string('type')->default('receipt')->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_ins', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
