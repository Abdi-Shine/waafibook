<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // status was an enum with a single value 'paid' — impossible to add
        // draft/pending states without an ALTER. Convert to a plain string
        // now so future workflow states can be added without schema changes.
        Schema::table('purchase_expenses', function (Blueprint $table) {
            $table->string('status')->default('paid')->change();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_expenses', function (Blueprint $table) {
            $table->enum('status', ['paid'])->default('paid')->change();
        });
    }
};
