<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // product_stocks and branch_transfers use integer for quantity while
        // every transaction table uses decimal(15,2). Fractional sales/purchases
        // (e.g. 0.50 kg) silently truncate the stock balance.
        Schema::table('product_stocks', function (Blueprint $table) {
            $table->decimal('quantity', 15, 2)->default(0)->change();
        });

        Schema::table('branch_transfers', function (Blueprint $table) {
            $table->decimal('quantity', 15, 2)->change();
        });
    }

    public function down(): void
    {
        Schema::table('product_stocks', function (Blueprint $table) {
            $table->integer('quantity')->default(0)->change();
        });

        Schema::table('branch_transfers', function (Blueprint $table) {
            $table->integer('quantity')->change();
        });
    }
};
