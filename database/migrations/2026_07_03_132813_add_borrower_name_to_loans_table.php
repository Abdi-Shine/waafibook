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
        Schema::table('loans', function (Blueprint $table) {
            $table->string('borrower_name')->nullable()->after('employee_id');
            $table->string('borrower_type')->default('individual')->after('borrower_name');
            $table->string('phone')->nullable()->after('borrower_type');
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropColumn(['borrower_name', 'borrower_type', 'phone']);
        });
    }
};
