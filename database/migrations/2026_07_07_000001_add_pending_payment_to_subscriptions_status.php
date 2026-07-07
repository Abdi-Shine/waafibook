<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE subscriptions MODIFY COLUMN status ENUM('active','expired','canceled','cancelled','trial','pending_payment') NOT NULL DEFAULT 'active'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE subscriptions MODIFY COLUMN status ENUM('active','expired','canceled','cancelled','trial') NOT NULL DEFAULT 'active'");
    }
};
