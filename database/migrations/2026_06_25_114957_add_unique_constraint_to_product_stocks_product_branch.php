<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Nothing previously stopped two rows existing for the same
        // product+branch — under concurrent requests, code that does
        // "find or create, then update" could create a duplicate instead of
        // finding the existing row, after which different code paths might
        // read/write either one and silently disagree on the real quantity.
        // Consolidate any duplicates (summing their quantities into the
        // oldest row) before the constraint is added, so this is safe to run
        // against existing data, not just a fresh database.
        $duplicateGroups = DB::table('product_stocks')
            ->select('product_id', 'branch_id')
            ->selectRaw('COUNT(*) as cnt')
            ->groupBy('product_id', 'branch_id')
            ->having('cnt', '>', 1)
            ->get();

        foreach ($duplicateGroups as $group) {
            $rowsQuery = DB::table('product_stocks')->where('product_id', $group->product_id);
            $rowsQuery = $group->branch_id === null
                ? $rowsQuery->whereNull('branch_id')
                : $rowsQuery->where('branch_id', $group->branch_id);

            $rows = $rowsQuery->orderBy('id')->get();
            $keep = $rows->first();
            $totalQuantity = $rows->sum('quantity');

            DB::table('product_stocks')->where('id', $keep->id)->update(['quantity' => $totalQuantity]);
            DB::table('product_stocks')->whereIn('id', $rows->skip(1)->pluck('id'))->delete();
        }

        // A plain index on product_id alone, so the foreign key below has
        // something to depend on other than the composite unique index added
        // next — otherwise dropping that unique index later (down()) fails
        // with "needed in a foreign key constraint", since it's currently the
        // only index that starts with product_id.
        Schema::table('product_stocks', function (Blueprint $table) {
            $table->index('product_id', 'product_stocks_product_id_plain_index');
        });

        Schema::table('product_stocks', function (Blueprint $table) {
            $table->unique(['product_id', 'branch_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // The plain index is intentionally left in place — product_id has a
        // foreign key, and InnoDB requires at least one index starting with
        // a foreign-keyed column at all times, so dropping this one isn't
        // actually reversible without also dropping the FK first.
        Schema::table('product_stocks', function (Blueprint $table) {
            $table->dropUnique(['product_id', 'branch_id']);
        });
    }
};
