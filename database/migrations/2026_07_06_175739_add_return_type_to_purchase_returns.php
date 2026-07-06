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
        Schema::table('purchase_returns', function (Blueprint $table) {
            $table->string('return_type')->default('credit')->after('status');
            $table->foreignId('refund_account_id')->nullable()->after('return_type')
                  ->constrained('chart_of_accounts')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_returns', function (Blueprint $table) {
            $table->dropForeign(['refund_account_id']);
            $table->dropColumn(['return_type', 'refund_account_id']);
        });
    }
};
