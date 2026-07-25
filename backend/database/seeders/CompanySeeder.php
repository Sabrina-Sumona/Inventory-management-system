<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        Company::updateOrCreate(
            [
                'code' => 'DESH-SOLAR',
            ],
            [
                'name' => 'Desh Solar',
                'email' => null,
                'website' => 'https://deshsolar.com/',
                'phone' => null,
                'address' => null,
                'timezone' => 'Asia/Dhaka',
                'currency' => 'BDT',
                'is_active' => true,
            ]
        );
    }
}