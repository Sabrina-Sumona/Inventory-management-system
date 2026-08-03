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
        $company = Company::query()
            ->where(
                'code',
                'DESH-SOLAR'
            )
            ->firstOrFail();

        $roles = [
            [
                'company_id' => null,
                'name' => 'Super Admin',
                'code' => 'SUPER-ADMIN',
                'description' =>
                    'Global administrator with complete system access.',
                'is_system' => true,
                'permissions' => '*',
            ],
            [
                'company_id' => $company->id,
                'name' => 'Company Admin',
                'code' =>
                    'DESH-SOLAR-COMPANY-ADMIN',
                'description' =>
                    'Manages Desh Solar organization, warehouses, suppliers, customers, users, and access control.',
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

                    'supplier.view',
                    'supplier.create',
                    'supplier.update',
                    'supplier.delete',

                    'supplier-contact.view',
                    'supplier-contact.create',
                    'supplier-contact.update',
                    'supplier-contact.delete',

                    'supplier-financial-setting.view',
                    'supplier-financial-setting.create',
                    'supplier-financial-setting.update',
                    'supplier-financial-setting.delete',

                    'customer.view',
                    'customer.create',
                    'customer.update',
                    'customer.delete',

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
                'code' =>
                    'DESH-SOLAR-INVENTORY-MANAGER',
                'description' =>
                    'Manages inventory operations, warehouse records, suppliers, customers, supplier contacts, and supplier financial settings.',
                'is_system' => false,
                'permissions' => [
                    'company.view',
                    'branch.view',

                    'warehouse.view',
                    'warehouse.create',
                    'warehouse.update',

                    'supplier.view',
                    'supplier.create',
                    'supplier.update',

                    'supplier-contact.view',
                    'supplier-contact.create',
                    'supplier-contact.update',

                    'supplier-financial-setting.view',
                    'supplier-financial-setting.create',
                    'supplier-financial-setting.update',

                    'customer.view',
                    'customer.create',
                    'customer.update',

                    'user.view',
                    'role.view',
                ],
            ],
            [
                'company_id' => $company->id,
                'name' => 'Warehouse Manager',
                'code' =>
                    'DESH-SOLAR-WAREHOUSE-MANAGER',
                'description' =>
                    'Manages assigned warehouse operations and views permitted supplier, customer, contact, and financial information.',
                'is_system' => false,
                'permissions' => [
                    'company.view',
                    'branch.view',

                    'warehouse.view',
                    'warehouse.update',

                    'supplier.view',
                    'supplier-contact.view',
                    'supplier-financial-setting.view',

                    'customer.view',
                ],
            ],
            [
                'company_id' => $company->id,
                'name' => 'Storekeeper',
                'code' =>
                    'DESH-SOLAR-STOREKEEPER',
                'description' =>
                    'Handles day-to-day warehouse inventory operations and views permitted supplier and customer information.',
                'is_system' => false,
                'permissions' => [
                    'company.view',
                    'branch.view',
                    'warehouse.view',

                    'supplier.view',
                    'supplier-contact.view',
                    'supplier-financial-setting.view',

                    'customer.view',
                ],
            ],
            [
                'company_id' => $company->id,
                'name' => 'Viewer',
                'code' =>
                    'DESH-SOLAR-VIEWER',
                'description' =>
                    'Read-only access to permitted organization, supplier, customer, contact, and financial-setting records.',
                'is_system' => false,
                'permissions' => [
                    'company.view',
                    'branch.view',
                    'warehouse.view',

                    'supplier.view',
                    'supplier-contact.view',
                    'supplier-financial-setting.view',

                    'customer.view',
                ],
            ],
        ];

        foreach ($roles as $roleData) {
            $permissionCodes =
                $roleData['permissions'];

            unset(
                $roleData['permissions']
            );

            $role = Role::query()
                ->updateOrCreate(
                    [
                        'code' =>
                            $roleData['code'],
                    ],
                    [
                        ...$roleData,
                        'is_active' => true,
                    ]
                );

            $permissionIds =
                $permissionCodes === '*'
                    ? Permission::query()
                        ->where(
                            'is_active',
                            true
                        )
                        ->pluck('id')
                        ->all()
                    : Permission::query()
                        ->whereIn(
                            'code',
                            $permissionCodes
                        )
                        ->where(
                            'is_active',
                            true
                        )
                        ->pluck('id')
                        ->all();

            $role
                ->permissions()
                ->sync(
                    $permissionIds
                );
        }
    }
}