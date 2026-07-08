<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // brands table exists but products had no brand_id — the feature was
        // half-built. Adding the FK column completes it.
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('brand_id')
                  ->nullable()
                  ->after('category_id')
                  ->constrained('brands')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['brand_id']);
            $table->dropColumn('brand_id');
        });
    }
};
