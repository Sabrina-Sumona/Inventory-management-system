<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('code', 'DESH-SOLAR')
            ->firstOrFail();

        $roles = [
            [
                'company_id' => null,
                'name' => 'Super Admin',
                'code' => 'SUPER-ADMIN',
                'description' => 'Global administrator with complete system access.',
                'is_system' => true,
                'permissions' => '*',
            ],
            [
                'company_id' => $company->id,
                'name' => 'Company Admin',
                'code' => 'DESH-SOLAR-COMPANY-ADMIN',
                'description' => 'Manages Desh Solar organization and users.',
                'is_system' => false,
                'permissions' => [
                    'company.view',
                    'company.update',

                    'branch.view',
                    'branch.create',
                    'branch.update',
                    'branch.delete',

                    'warehouse.view',
                    'warehouse.create',
                    'warehouse.update',
                    'warehouse.delete',

                    'warehouse-location.view',
                    'warehouse-location.create',
                    'warehouse-location.update',
                    'warehouse-location.delete',

                    'user.view',
                    'user.create',
                    'user.update',
                    'user.deactivate',

                    'role.view',
                    'role.create',
                    'role.update',
                    'role.assign',

                    'permission.view',
                ],
            ],
            [
                'company_id' => $company->id,
                'name' => 'Inventory Manager',
                'code' => 'DESH-SOLAR-INVENTORY-MANAGER',
                'description' => 'Manages inventory and warehouse structures.',
                'is_system' => false,
                'permissions' => [
                    'company.view',
                    'branch.view',

                    'warehouse.view',
                    'warehouse.create',
                    'warehouse.update',

                    'warehouse-location.view',
                    'warehouse-location.create',
                    'warehouse-location.update',

                    'user.view',
                    'role.view',
                ],
            ],
            [
                'company_id' => $company->id,
                'name' => 'Warehouse Manager',
                'code' => 'DESH-SOLAR-WAREHOUSE-MANAGER',
                'description' => 'Manages warehouse and storage-location operations.',
                'is_system' => false,
                'permissions' => [
                    'company.view',
                    'branch.view',
                    'warehouse.view',
                    'warehouse.update',

                    'warehouse-location.view',
                    'warehouse-location.create',
                    'warehouse-location.update',
                ],
            ],
            [
                'company_id' => $company->id,
                'name' => 'Storekeeper',
                'code' => 'DESH-SOLAR-STOREKEEPER',
                'description' => 'Handles day-to-day warehouse storage operations.',
                'is_system' => false,
                'permissions' => [
                    'company.view',
                    'branch.view',
                    'warehouse.view',
                    'warehouse-location.view',
                ],
            ],
            [
                'company_id' => $company->id,
                'name' => 'Viewer',
                'code' => 'DESH-SOLAR-VIEWER',
                'description' => 'Read-only access to permitted organization records.',
                'is_system' => false,
                'permissions' => [
                    'company.view',
                    'branch.view',
                    'warehouse.view',
                    'warehouse-location.view',
                ],
            ],
        ];

        foreach ($roles as $roleData) {
            $permissionCodes = $roleData['permissions'];

            unset($roleData['permissions']);

            $role = Role::updateOrCreate(
                [
                    'code' => $roleData['code'],
                ],
                [
                    ...$roleData,
                    'is_active' => true,
                ]
            );

            $permissionIds = $permissionCodes === '*'
                ? Permission::query()
                    ->where('is_active', true)
                    ->pluck('id')
                    ->all()
                : Permission::query()
                    ->whereIn('code', $permissionCodes)
                    ->where('is_active', true)
                    ->pluck('id')
                    ->all();

            $role->permissions()->sync($permissionIds);
        }
    }
}