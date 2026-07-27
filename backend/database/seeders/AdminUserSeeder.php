<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('code', 'DESH-SOLAR')
            ->firstOrFail();

        $branch = Branch::where('company_id', $company->id)
            ->where('code', 'HEAD-OFFICE')
            ->firstOrFail();

        $warehouse = Warehouse::where('company_id', $company->id)
            ->where('branch_id', $branch->id)
            ->where('code', 'MAIN-WAREHOUSE')
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

        $user->assignBranch(
            branch: $branch,
            isPrimary: true,
        );

        $user->assignWarehouse(
            warehouse: $warehouse,
            isPrimary: true,
        );
    }
}