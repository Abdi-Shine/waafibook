<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserTableSeeder extends Seeder
{
    public function run(): void
    {
        // Remove old company admin if it exists
        User::withoutGlobalScopes()->where('email', 'admin@waafibook.com')->delete();

        // Super Admin — no company, uses /host/dashboard
        User::withoutGlobalScopes()->updateOrCreate(
            ['email' => 'superadmin@waafibook.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('Admin@2026#/2026'),
                'company_id' => null,
                'role' => 'Super Admin',
                'email_verified_at' => now(),
            ]
        );
    }
}
