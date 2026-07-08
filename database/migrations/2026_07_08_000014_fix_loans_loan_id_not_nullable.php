<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // loan_id is UNIQUE but nullable — MySQL allows unlimited NULLs in a
        // UNIQUE column, so the constraint never fires for loans created without
        // an explicit ID. Backfill NULLs with generated IDs, then make it required.
        $rows = DB::table('loans')->whereNull('loan_id')->pluck('id');

        foreach ($rows as $id) {
            DB::table('loans')->where('id', $id)->update([
                'loan_id' => 'LN-' . str_pad($id, 6, '0', STR_PAD_LEFT),
            ]);
        }

        Schema::table('loans', function (Blueprint $table) {
            $table->string('loan_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->string('loan_id')->nullable()->change();
        });
    }
};
