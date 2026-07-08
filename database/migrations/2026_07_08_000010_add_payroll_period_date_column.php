<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // month_year stores 'March 2026' as a string — ORDER BY produces
        // alphabetical order (April, August…). Add a proper date column
        // and backfill it. The old string column is kept for now so existing
        // Blade views continue to render without changes.
        if (!Schema::hasColumn('payrolls', 'payroll_period')) {
            Schema::table('payrolls', function (Blueprint $table) {
                $table->date('payroll_period')->nullable()->after('month_year');
                $table->index('payroll_period');
            });
        }

        // month_year is stored as 'YYYY-MM' — append '-01' to get the first day
        DB::statement("UPDATE payrolls
            SET payroll_period = STR_TO_DATE(CONCAT(month_year, '-01'), '%Y-%m-%d')
            WHERE month_year IS NOT NULL AND month_year != '' AND payroll_period IS NULL");
    }

    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropIndex(['payroll_period']);
            $table->dropColumn('payroll_period');
        });
    }
};
