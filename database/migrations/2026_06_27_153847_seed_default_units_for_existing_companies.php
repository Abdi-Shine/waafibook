<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // The product form's "Base Unit" dropdown previously hardcoded this same
    // set of options instead of reading from the units table. Backfilling
    // them here for every existing company keeps that switch from leaving
    // companies with an empty Units list.
    private array $defaults = ['Piece', 'Box', 'Pack', 'Carton', 'Dozen', 'kg', 'g', 'Meter', 'Liter'];

    public function up(): void
    {
        $now = now();
        foreach (DB::table('companies')->pluck('id') as $companyId) {
            $existing = DB::table('units')->where('company_id', $companyId)->pluck('name')->all();
            $rows = [];
            foreach ($this->defaults as $name) {
                if (!in_array($name, $existing, true)) {
                    $rows[] = ['company_id' => $companyId, 'name' => $name, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now];
                }
            }
            if (!empty($rows)) {
                DB::table('units')->insert($rows);
            }
        }
    }

    public function down(): void
    {
        DB::table('units')->whereIn('name', $this->defaults)->delete();
    }
};
