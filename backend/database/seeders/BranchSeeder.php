<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Company;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('code', 'DESH-SOLAR')->firstOrFail();

        Branch::updateOrCreate(
            [
                'company_id' => $company->id,
                'code' => 'HEAD-OFFICE',
            ],
            [
                'name' => 'Desh Solar Head Office',
                'email' => null,
                'phone' => null,
                'address' => null,
                'city' => null,
                'district' => null,
                'postal_code' => null,
                'is_head_office' => true,
                'is_active' => true,
            ]
        );
    }
}