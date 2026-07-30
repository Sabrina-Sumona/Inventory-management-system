<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CompanySeeder::class,
            BranchSeeder::class,
            WarehouseSeeder::class,

            PermissionSeeder::class,
            RoleSeeder::class,

            AdminUserSeeder::class,
        ]);
    }
}