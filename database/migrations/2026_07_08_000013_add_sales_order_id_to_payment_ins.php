<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_ins', function (Blueprint $table) {
            $table->unsignedBigInteger('sales_order_id')->nullable()->after('invoice_no');
            $table->index('sales_order_id');
        });

        // Backfill: match on invoice_no + company_id to find the sales order
        DB::statement("UPDATE payment_ins pi
            JOIN sales_orders so
                ON so.invoice_no = pi.invoice_no
               AND so.company_id = pi.company_id
            SET pi.sales_order_id = so.id
            WHERE pi.invoice_no IS NOT NULL AND pi.invoice_no != ''");

        Schema::table('payment_ins', function (Blueprint $table) {
            $table->foreign('sales_order_id')
                  ->references('id')->on('sales_orders')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payment_ins', function (Blueprint $table) {
            $table->dropForeign(['sales_order_id']);
            $table->dropIndex(['sales_order_id']);
            $table->dropColumn('sales_order_id');
        });
    }
};
