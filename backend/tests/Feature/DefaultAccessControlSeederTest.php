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
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount(
            'permissions',
            23
        );

        $this->assertDatabaseCount(
            'roles',
            6
        );

        $this->assertDatabaseHas('roles', [
            'code' => 'SUPER-ADMIN',
            'company_id' => null,
            'is_system' => true,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('roles', [
            'code' =>
                'DESH-SOLAR-WAREHOUSE-MANAGER',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('permissions', [
            'code' => 'warehouse.view',
            'module' => 'warehouse',
            'action' => 'view',
        ]);

        $this->assertDatabaseHas('permissions', [
            'code' => 'supplier.view',
            'module' => 'supplier',
            'action' => 'view',
        ]);

        $this->assertDatabaseHas('permissions', [
            'code' => 'supplier.create',
            'module' => 'supplier',
            'action' => 'create',
        ]);

        $this->assertDatabaseHas('permissions', [
            'code' => 'supplier.update',
            'module' => 'supplier',
            'action' => 'update',
        ]);

        $this->assertDatabaseHas('permissions', [
            'code' => 'supplier.delete',
            'module' => 'supplier',
            'action' => 'delete',
        ]);

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
        $this->seed(DatabaseSeeder::class);

        $superAdmin = Role::where(
            'code',
            'SUPER-ADMIN'
        )->firstOrFail();

        $this->assertSame(
            Permission::count(),
            $superAdmin
                ->permissions()
                ->count()
        );
    }

    public function test_warehouse_manager_receives_expected_permissions(): void
    {
        $this->seed(DatabaseSeeder::class);

        $role = Role::where(
            'code',
            'DESH-SOLAR-WAREHOUSE-MANAGER'
        )->firstOrFail();

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
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount(
            'permissions',
            23
        );

        $this->assertDatabaseCount(
            'roles',
            6
        );

        $warehouseViewPermission =
            Permission::where(
                'code',
                'warehouse.view'
            )->firstOrFail();

        $supplierViewPermission =
            Permission::where(
                'code',
                'supplier.view'
            )->firstOrFail();

        $warehouseManager = Role::where(
            'code',
            'DESH-SOLAR-WAREHOUSE-MANAGER'
        )->firstOrFail();

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

        $this->assertDatabaseMissing(
            'permissions',
            [
                'module' =>
                    'warehouse-location',
            ]
        );
    }
}