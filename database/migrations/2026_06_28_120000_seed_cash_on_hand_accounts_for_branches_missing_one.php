<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $branches = DB::table('branches')->get();

        foreach ($branches as $branch) {
            $hasCashAccount = DB::table('chart_of_accounts')
                ->where('company_id', $branch->company_id)
                ->where('branch_id', $branch->id)
                ->where('type', 'cash')
                ->exists();

            if ($hasCashAccount) {
                continue;
            }

            DB::table('chart_of_accounts')->insert([
                'company_id' => $branch->company_id,
                'branch_id'  => $branch->id,
                'code'       => '1110-' . $branch->id,
                'name'       => $branch->name . ' - Cash on Hand',
                'category'   => 'assets',
                'type'       => 'cash',
                'balance'    => 0,
                'is_active'  => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('chart_of_accounts')
            ->where('code', 'like', '1110-%')
            ->delete();
    }
};
