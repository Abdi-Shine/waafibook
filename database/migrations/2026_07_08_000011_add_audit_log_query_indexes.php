<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // audit_logs grows faster than any other table. Every filter on the
        // Security page (module, action, date range, per-entity history) does
        // a full table scan today.
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->index(['record_type', 'record_id']);
            $table->index('module');
            $table->index('action');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex(['record_type', 'record_id']);
            $table->dropIndex(['module']);
            $table->dropIndex(['action']);
            $table->dropIndex(['created_at']);
        });
    }
};
