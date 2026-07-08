<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // size was stored as a string — prevents ORDER BY size and SUM(size).
        // Strip any non-numeric characters (e.g. '50 MB' → 0) before converting.
        DB::statement("UPDATE backups
            SET size = CASE
                WHEN size REGEXP '^[0-9]+$' THEN size
                ELSE '0'
            END");

        Schema::table('backups', function (Blueprint $table) {
            $table->unsignedBigInteger('size')->nullable()->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('backups', function (Blueprint $table) {
            $table->string('size')->nullable()->change();
        });
    }
};
