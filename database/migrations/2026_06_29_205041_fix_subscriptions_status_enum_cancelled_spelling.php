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
        // The column was created with the enum spelled 'canceled', but every
        // place in the app (Host controller, Subscription::hasAccess(), the
        // subscriptions index view) writes/reads 'cancelled' — causing a
        // "Data truncated for column 'status'" error whenever a subscription
        // was actually cancelled.
        DB::statement("ALTER TABLE subscriptions MODIFY status ENUM('active','expired','cancelled','trial') NOT NULL DEFAULT 'active'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE subscriptions MODIFY status ENUM('active','expired','canceled','trial') NOT NULL DEFAULT 'active'");
    }
};
