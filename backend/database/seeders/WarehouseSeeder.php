<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('code', 'DESH-SOLAR')->firstOrFail();

        $branch = Branch::where('company_id', $company->id)
            ->where('code', 'HEAD-OFFICE')
            ->firstOrFail();

        Warehouse::updateOrCreate(
            [
                'company_id' => $company->id,
                'code' => 'MAIN-WAREHOUSE',
            ],
            [
                'branch_id' => $branch->id,
                'name' => 'Desh Solar Main Warehouse',
                'email' => null,
                'phone' => null,
                'address' => null,
                'city' => null,
                'district' => null,
                'postal_code' => null,
                'is_primary' => true,
                'is_active' => true,
            ]
        );
    }
}