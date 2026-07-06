<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Services\ChartOfAccountsService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ChartOfAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $service = app(ChartOfAccountsService::class);

        // Find all companies or create a demo one if none exist
        $companies = Company::withoutGlobalScopes()->get();
        if ($companies->isEmpty()) {
            $company = Company::create([
                'name' => 'Waafibook',
                'email' => 'admin@waafibook.com',
                'currency' => 'USD',
            ]);
            $companies = collect([$company]);
        }

        foreach ($companies as $company) {
            $this->command?->info("  Seeding chart of accounts for: {$company->name}");
            $branchId = $this->seedDefaultBranch($company->id, $company->name);
            $service->seedForCompany($company->id, $branchId);
        }
    }

    private function seedDefaultBranch(int $companyId, string $companyName): int
    {
        DB::table('branches')->updateOrInsert(
            ['company_id' => $companyId, 'code' => 'BR-HQ'],
            [
                'name' => $companyName . ' - HQ',
                'level' => 'Headquarters',
                'is_active' => 1,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return DB::table('branches')
            ->where('company_id', $companyId)
            ->where('code', 'BR-HQ')
            ->value('id');
    }
}
