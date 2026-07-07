<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $allActions  = ['view', 'create', 'edit', 'delete'];
        $modules     = [
            'Dashboard', 'Sales & POS', 'Customers', 'Suppliers',
            'Products', 'Purchases', 'Accounting', 'HR',
            'Reports', 'Settings',
        ];
        $fullPermissions     = array_fill_keys($modules, $allActions);
        $viewOnlyPermissions = array_fill_keys($modules, ['view']);

        $defaultRoles = [
            ['name' => 'Manager', 'description' => 'Full access to all modules',     'permissions' => $fullPermissions],
            ['name' => 'Cashier', 'description' => 'Sales and POS access',            'permissions' => array_fill_keys(['Dashboard', 'Sales & POS'], $allActions)],
            ['name' => 'Staff',   'description' => 'View-only access to all modules', 'permissions' => $viewOnlyPermissions],
        ];

        // Seed roles for every existing company
        $companyIds = Company::withoutGlobalScopes()->pluck('id');

        foreach ($companyIds as $cid) {
            foreach ($defaultRoles as $roleData) {
                Role::withoutGlobalScopes()->updateOrCreate(
                    ['company_id' => $cid, 'name' => $roleData['name']],
                    array_merge($roleData, ['company_id' => $cid])
                );
            }
        }
    }
}
