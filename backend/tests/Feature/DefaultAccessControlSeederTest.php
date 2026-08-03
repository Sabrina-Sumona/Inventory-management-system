<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DefaultAccessControlSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_permissions_and_roles_are_seeded(): void
    {
        $this->seed(
            DatabaseSeeder::class
        );

        $this->assertDatabaseCount(
            'permissions',
            43
        );

        $this->assertDatabaseCount(
            'roles',
            6
        );

        $this->assertDatabaseHas(
            'roles',
            [
                'code' => 'SUPER-ADMIN',
                'company_id' => null,
                'is_system' => true,
                'is_active' => true,
            ]
        );

        $this->assertDatabaseHas(
            'roles',
            [
                'code' =>
                    'DESH-SOLAR-WAREHOUSE-MANAGER',

                'is_active' => true,
            ]
        );

        $this->assertDatabaseHas(
            'permissions',
            [
                'code' => 'warehouse.view',
                'module' => 'warehouse',
                'action' => 'view',
            ]
        );

        $this->assertDatabaseHas(
            'permissions',
            [
                'code' => 'supplier.view',
                'module' => 'supplier',
                'action' => 'view',
            ]
        );

        $this->assertDatabaseHas(
            'permissions',
            [
                'code' => 'supplier.create',
                'module' => 'supplier',
                'action' => 'create',
            ]
        );

        $this->assertDatabaseHas(
            'permissions',
            [
                'code' => 'supplier.update',
                'module' => 'supplier',
                'action' => 'update',
            ]
        );

        $this->assertDatabaseHas(
            'permissions',
            [
                'code' => 'supplier.delete',
                'module' => 'supplier',
                'action' => 'delete',
            ]
        );

        $this->assertDatabaseHas(
            'permissions',
            [
                'code' =>
                    'supplier-contact.view',

                'module' =>
                    'supplier-contact',

                'action' => 'view',
            ]
        );

        $this->assertDatabaseHas(
            'permissions',
            [
                'code' =>
                    'supplier-contact.create',

                'module' =>
                    'supplier-contact',

                'action' => 'create',
            ]
        );

        $this->assertDatabaseHas(
            'permissions',
            [
                'code' =>
                    'supplier-contact.update',

                'module' =>
                    'supplier-contact',

                'action' => 'update',
            ]
        );

        $this->assertDatabaseHas(
            'permissions',
            [
                'code' =>
                    'supplier-contact.delete',

                'module' =>
                    'supplier-contact',

                'action' => 'delete',
            ]
        );

        $this->assertDatabaseHas(
            'permissions',
            [
                'code' =>
                    'supplier-financial-setting.view',

                'module' =>
                    'supplier-financial-setting',

                'action' => 'view',
            ]
        );

        $this->assertDatabaseHas(
            'permissions',
            [
                'code' =>
                    'supplier-financial-setting.create',

                'module' =>
                    'supplier-financial-setting',

                'action' => 'create',
            ]
        );

        $this->assertDatabaseHas(
            'permissions',
            [
                'code' =>
                    'supplier-financial-setting.update',

                'module' =>
                    'supplier-financial-setting',

                'action' => 'update',
            ]
        );

        $this->assertDatabaseHas(
            'permissions',
            [
                'code' =>
                    'supplier-financial-setting.delete',

                'module' =>
                    'supplier-financial-setting',

                'action' => 'delete',
            ]
        );

        $this->assertDatabaseHas(
            'permissions',
            [
                'code' => 'customer.view',
                'module' => 'customer',
                'action' => 'view',
            ]
        );

        $this->assertDatabaseHas(
            'permissions',
            [
                'code' => 'customer.create',
                'module' => 'customer',
                'action' => 'create',
            ]
        );

        $this->assertDatabaseHas(
            'permissions',
            [
                'code' => 'customer.update',
                'module' => 'customer',
                'action' => 'update',
            ]
        );

        $this->assertDatabaseHas(
            'permissions',
            [
                'code' => 'customer.delete',
                'module' => 'customer',
                'action' => 'delete',
            ]
        );

        $this->assertDatabaseHas(
            'permissions',
            [
                'code' =>
                    'customer-contact.view',

                'module' =>
                    'customer-contact',

                'action' => 'view',
            ]
        );

        $this->assertDatabaseHas(
            'permissions',
            [
                'code' =>
                    'customer-contact.create',

                'module' =>
                    'customer-contact',

                'action' => 'create',
            ]
        );

        $this->assertDatabaseHas(
            'permissions',
            [
                'code' =>
                    'customer-contact.update',

                'module' =>
                    'customer-contact',

                'action' => 'update',
            ]
        );

        $this->assertDatabaseHas(
            'permissions',
            [
                'code' =>
                    'customer-contact.delete',

                'module' =>
                    'customer-contact',

                'action' => 'delete',
            ]
        );

        $this->assertDatabaseHas(
            'permissions',
            [
                'code' =>
                    'customer-financial-setting.view',

                'module' =>
                    'customer-financial-setting',

                'action' => 'view',
            ]
        );

        $this->assertDatabaseHas(
            'permissions',
            [
                'code' =>
                    'customer-financial-setting.create',

                'module' =>
                    'customer-financial-setting',

                'action' => 'create',
            ]
        );

        $this->assertDatabaseHas(
            'permissions',
            [
                'code' =>
                    'customer-financial-setting.update',

                'module' =>
                    'customer-financial-setting',

                'action' => 'update',
            ]
        );

        $this->assertDatabaseHas(
            'permissions',
            [
                'code' =>
                    'customer-financial-setting.delete',

                'module' =>
                    'customer-financial-setting',

                'action' => 'delete',
            ]
        );

        $this->assertDatabaseMissing(
            'permissions',
            [
                'module' =>
                    'warehouse-location',
            ]
        );
    }

    public function test_super_admin_receives_every_permission(): void
    {
        $this->seed(
            DatabaseSeeder::class
        );

        $superAdmin = Role::query()
            ->where(
                'code',
                'SUPER-ADMIN'
            )
            ->firstOrFail();

        $this->assertSame(
            Permission::query()->count(),
            $superAdmin
                ->permissions()
                ->count()
        );
    }

    public function test_warehouse_manager_receives_expected_permissions(): void
    {
        $this->seed(
            DatabaseSeeder::class
        );

        $role = Role::query()
            ->where(
                'code',
                'DESH-SOLAR-WAREHOUSE-MANAGER'
            )
            ->firstOrFail();

        $this->assertTrue(
            $role->hasPermission(
                'warehouse.view'
            )
        );

        $this->assertTrue(
            $role->hasPermission(
                'warehouse.update'
            )
        );

        $this->assertTrue(
            $role->hasPermission(
                'supplier.view'
            )
        );

        $this->assertTrue(
            $role->hasPermission(
                'supplier-contact.view'
            )
        );

        $this->assertTrue(
            $role->hasPermission(
                'supplier-financial-setting.view'
            )
        );

        $this->assertFalse(
            $role->hasPermission(
                'supplier.create'
            )
        );

        $this->assertFalse(
            $role->hasPermission(
                'supplier.update'
            )
        );

        $this->assertFalse(
            $role->hasPermission(
                'supplier.delete'
            )
        );

        $this->assertFalse(
            $role->hasPermission(
                'supplier-contact.create'
            )
        );

        $this->assertFalse(
            $role->hasPermission(
                'supplier-contact.update'
            )
        );

        $this->assertFalse(
            $role->hasPermission(
                'supplier-contact.delete'
            )
        );

        $this->assertFalse(
            $role->hasPermission(
                'supplier-financial-setting.create'
            )
        );

        $this->assertFalse(
            $role->hasPermission(
                'supplier-financial-setting.update'
            )
        );

        $this->assertFalse(
            $role->hasPermission(
                'supplier-financial-setting.delete'
            )
        );

        $this->assertFalse(
            $role->hasPermission(
                'user.create'
            )
        );

        $this->assertFalse(
            $role->hasPermission(
                'warehouse.delete'
            )
        );

        $this->assertFalse(
            $role->hasPermission(
                'warehouse-location.update'
            )
        );
    }

    public function test_seeders_can_run_more_than_once_without_duplicates(): void
    {
        $this->seed(
            DatabaseSeeder::class
        );

        $this->seed(
            DatabaseSeeder::class
        );

        $this->assertDatabaseCount(
            'permissions',
            43
        );

        $this->assertDatabaseCount(
            'roles',
            6
        );

        $warehouseViewPermission =
            Permission::query()
                ->where(
                    'code',
                    'warehouse.view'
                )
                ->firstOrFail();

        $supplierViewPermission =
            Permission::query()
                ->where(
                    'code',
                    'supplier.view'
                )
                ->firstOrFail();

        $supplierContactViewPermission =
            Permission::query()
                ->where(
                    'code',
                    'supplier-contact.view'
                )
                ->firstOrFail();

        $supplierFinancialSettingViewPermission =
            Permission::query()
                ->where(
                    'code',
                    'supplier-financial-setting.view'
                )
                ->firstOrFail();

        $warehouseManager =
            Role::query()
                ->where(
                    'code',
                    'DESH-SOLAR-WAREHOUSE-MANAGER'
                )
                ->firstOrFail();

        $this->assertDatabaseHas(
            'role_permission',
            [
                'role_id' =>
                    $warehouseManager->id,

                'permission_id' =>
                    $warehouseViewPermission->id,
            ]
        );

        $this->assertDatabaseHas(
            'role_permission',
            [
                'role_id' =>
                    $warehouseManager->id,

                'permission_id' =>
                    $supplierViewPermission->id,
            ]
        );

        $this->assertDatabaseHas(
            'role_permission',
            [
                'role_id' =>
                    $warehouseManager->id,

                'permission_id' =>
                    $supplierContactViewPermission->id,
            ]
        );

        $this->assertDatabaseHas(
            'role_permission',
            [
                'role_id' =>
                    $warehouseManager->id,

                'permission_id' =>
                    $supplierFinancialSettingViewPermission->id,
            ]
        );

        $this->assertDatabaseMissing(
            'permissions',
            [
                'module' =>
                    'warehouse-location',
            ]
        );
    }
}