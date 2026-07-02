<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Backfill companies.email from their first user's email where missing.
        DB::statement("
            UPDATE companies
            SET email = (
                SELECT email FROM users
                WHERE users.company_id = companies.id
                ORDER BY users.id ASC
                LIMIT 1
            )
            WHERE (email IS NULL OR email = '')
            AND EXISTS (
                SELECT 1 FROM users WHERE users.company_id = companies.id
            )
        ");
    }

    public function down(): void {}
};
