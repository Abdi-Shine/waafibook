<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add user_id index — currently missing, lookups from user→employee
        //    hit a full table scan on every authenticated request.
        Schema::table('employees', function (Blueprint $table) {
            $table->index('user_id');
        });

        // 2. Add branch_id FK column alongside the existing 'branch' string.
        //    The string column is kept because User.php and employee.blade.php
        //    still read it. This FK column is for future use and reporting.
        Schema::table('employees', function (Blueprint $table) {
            $table->unsignedBigInteger('branch_id')->nullable()->after('branch');
            $table->index('branch_id');
        });

        // Backfill branch_id by matching branches.name to employees.branch
        // within the same company.
        DB::statement("UPDATE employees e
            JOIN branches b ON LOWER(b.name) = LOWER(e.branch)
                            AND b.company_id = e.company_id
            SET e.branch_id = b.id
            WHERE e.branch IS NOT NULL AND e.branch_id IS NULL");

        Schema::table('employees', function (Blueprint $table) {
            $table->foreign('branch_id')
                  ->references('id')->on('branches')
                  ->nullOnDelete();
        });

        // 3. Drop 'store' — the stores table was dropped in April 2026 and all
        //    store references in the app are now dead code. User.php lines 125-126
        //    reference Store model which no longer has a table; store is always NULL.
        // 4. Drop 'company' string — redundant with company_id FK; written by
        //    EmployeeController but the value is derivable via $employee->companyRelation->name.
        //    NOTE: EmployeeController still writes to 'company' — that code will
        //    need to be updated separately after this migration runs.
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['store']);
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropIndex(['branch_id']);
            $table->dropIndex(['user_id']);
            $table->dropColumn('branch_id');
            $table->string('store')->nullable()->after('branch');
        });
    }
};
