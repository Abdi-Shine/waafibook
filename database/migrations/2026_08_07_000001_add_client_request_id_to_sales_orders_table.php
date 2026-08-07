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
        Schema::table('sales_orders', function (Blueprint $table) {
            // Client-generated idempotency key (UUID). One checkout attempt —
            // however many times it's retried after a dropped connection —
            // must only ever produce one row here. Nullable so older clients
            // (or any caller that doesn't send one) keep working unchanged.
            $table->string('client_request_id')->nullable()->unique()->after('invoice_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropUnique(['client_request_id']);
            $table->dropColumn('client_request_id');
        });
    }
};
