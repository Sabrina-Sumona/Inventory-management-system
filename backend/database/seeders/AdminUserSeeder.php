<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('code', 'DESH-SOLAR')
            ->firstOrFail();

        $companyAdminRole = Role::where(
            'code',
            'DESH-SOLAR-COMPANY-ADMIN'
        )->firstOrFail();

        $user = User::updateOrCreate(
            [
                'email' => 'admin@deshsolar.com',
            ],
            [
                'company_id' => $company->id,
                'name' => 'Desh Solar Administrator',
                'password' => Hash::make('ChangeMe123!'),
                'email_verified_at' => now(),
            ]
        );

        $user->assignRole($companyAdminRole);
    }
}