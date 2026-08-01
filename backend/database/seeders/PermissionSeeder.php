<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Company
            [
                'name' => 'View Companies',
                'code' => 'company.view',
                'module' => 'company',
                'action' => 'view',
                'description' => 'View company information.',
            ],
            [
                'name' => 'Update Companies',
                'code' => 'company.update',
                'module' => 'company',
                'action' => 'update',
                'description' => 'Update company information.',
            ],

            // Branch
            [
                'name' => 'View Branches',
                'code' => 'branch.view',
                'module' => 'branch',
                'action' => 'view',
                'description' => 'View branch records.',
            ],
            [
                'name' => 'Create Branches',
                'code' => 'branch.create',
                'module' => 'branch',
                'action' => 'create',
                'description' => 'Create new branches.',
            ],
            [
                'name' => 'Update Branches',
                'code' => 'branch.update',
                'module' => 'branch',
                'action' => 'update',
                'description' => 'Update branch records.',
            ],
            [
                'name' => 'Delete Branches',
                'code' => 'branch.delete',
                'module' => 'branch',
                'action' => 'delete',
                'description' => 'Delete branch records.',
            ],

            // Warehouse
            [
                'name' => 'View Warehouses',
                'code' => 'warehouse.view',
                'module' => 'warehouse',
                'action' => 'view',
                'description' => 'View warehouse records.',
            ],
            [
                'name' => 'Create Warehouses',
                'code' => 'warehouse.create',
                'module' => 'warehouse',
                'action' => 'create',
                'description' => 'Create new warehouses.',
            ],
            [
                'name' => 'Update Warehouses',
                'code' => 'warehouse.update',
                'module' => 'warehouse',
                'action' => 'update',
                'description' => 'Update warehouse records.',
            ],
            [
                'name' => 'Delete Warehouses',
                'code' => 'warehouse.delete',
                'module' => 'warehouse',
                'action' => 'delete',
                'description' => 'Delete warehouse records.',
            ],

            // Supplier
            [
                'name' => 'View Suppliers',
                'code' => 'supplier.view',
                'module' => 'supplier',
                'action' => 'view',
                'description' => 'View supplier records.',
            ],
            [
                'name' => 'Create Suppliers',
                'code' => 'supplier.create',
                'module' => 'supplier',
                'action' => 'create',
                'description' => 'Create new suppliers.',
            ],
            [
                'name' => 'Update Suppliers',
                'code' => 'supplier.update',
                'module' => 'supplier',
                'action' => 'update',
                'description' => 'Update supplier records.',
            ],
            [
                'name' => 'Delete Suppliers',
                'code' => 'supplier.delete',
                'module' => 'supplier',
                'action' => 'delete',
                'description' => 'Delete supplier records.',
            ],

            // Supplier Contact
            [
                'name' => 'View Supplier Contacts',
                'code' => 'supplier-contact.view',
                'module' => 'supplier-contact',
                'action' => 'view',
                'description' => 'View supplier contact records.',
            ],
            [
                'name' => 'Create Supplier Contacts',
                'code' => 'supplier-contact.create',
                'module' => 'supplier-contact',
                'action' => 'create',
                'description' => 'Create new supplier contacts.',
            ],
            [
                'name' => 'Update Supplier Contacts',
                'code' => 'supplier-contact.update',
                'module' => 'supplier-contact',
                'action' => 'update',
                'description' => 'Update supplier contact records.',
            ],
            [
                'name' => 'Delete Supplier Contacts',
                'code' => 'supplier-contact.delete',
                'module' => 'supplier-contact',
                'action' => 'delete',
                'description' => 'Delete supplier contact records.',
            ],

            // Users
            [
                'name' => 'View Users',
                'code' => 'user.view',
                'module' => 'user',
                'action' => 'view',
                'description' => 'View system users.',
            ],
            [
                'name' => 'Create Users',
                'code' => 'user.create',
                'module' => 'user',
                'action' => 'create',
                'description' => 'Create system users.',
            ],
            [
                'name' => 'Update Users',
                'code' => 'user.update',
                'module' => 'user',
                'action' => 'update',
                'description' => 'Update system users.',
            ],
            [
                'name' => 'Deactivate Users',
                'code' => 'user.deactivate',
                'module' => 'user',
                'action' => 'deactivate',
                'description' => 'Deactivate system users.',
            ],

            // Roles and permissions
            [
                'name' => 'View Roles',
                'code' => 'role.view',
                'module' => 'role',
                'action' => 'view',
                'description' => 'View roles and assignments.',
            ],
            [
                'name' => 'Create Roles',
                'code' => 'role.create',
                'module' => 'role',
                'action' => 'create',
                'description' => 'Create company roles.',
            ],
            [
                'name' => 'Update Roles',
                'code' => 'role.update',
                'module' => 'role',
                'action' => 'update',
                'description' => 'Update company roles.',
            ],
            [
                'name' => 'Assign Roles',
                'code' => 'role.assign',
                'module' => 'role',
                'action' => 'assign',
                'description' => 'Assign roles to users.',
            ],
            [
                'name' => 'View Permissions',
                'code' => 'permission.view',
                'module' => 'permission',
                'action' => 'view',
                'description' => 'View available system permissions.',
            ],
        ];

        foreach ($permissions as $permissionData) {
            Permission::updateOrCreate(
                [
                    'code' => $permissionData['code'],
                ],
                [
                    ...$permissionData,
                    'is_system' => true,
                    'is_active' => true,
                ]
            );
        }

        Permission::query()
            ->where('module', 'warehouse-location')
            ->delete();
    }
}