<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sanitise any invalid JSON before converting the column type.
        // Rows with non-JSON content get set to NULL rather than crashing.
        DB::statement("UPDATE loans
            SET repayment_schedule = NULL
            WHERE repayment_schedule IS NOT NULL
            AND repayment_schedule != ''
            AND JSON_VALID(repayment_schedule) = 0");

        Schema::table('loans', function (Blueprint $table) {
            $table->json('repayment_schedule')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->text('repayment_schedule')->nullable()->change();
        });
    }
};
