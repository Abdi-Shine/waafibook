<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Migration 2026_07_07_000001 added 'pending_payment' but accidentally
        // reintroduced 'canceled' (one L) alongside 'cancelled' (two L's).
        // Normalise all rows to the correct spelling, then remove the typo.
        DB::statement("UPDATE subscriptions SET status = 'cancelled' WHERE status = 'canceled'");

        DB::statement("ALTER TABLE subscriptions MODIFY COLUMN status
            ENUM('active','expired','cancelled','trial','pending_payment')
            NOT NULL DEFAULT 'active'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE subscriptions MODIFY COLUMN status
            ENUM('active','expired','canceled','cancelled','trial','pending_payment')
            NOT NULL DEFAULT 'active'");
    }
};
