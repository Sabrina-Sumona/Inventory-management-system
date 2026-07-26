<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolePermissionRelationshipTest extends TestCase
{
    use RefreshDatabase;

    private function createCompany(): Company
    {
        return Company::create([
            'name' => 'Desh Solar',
            'code' => 'DESH-SOLAR',
            'website' => 'https://deshsolar.com/',
            'timezone' => 'Asia/Dhaka',
            'currency' => 'BDT',
            'is_active' => true,
        ]);
    }

    private function createRole(Company $company): Role
    {
        return Role::create([
            'company_id' => $company->id,
            'name' => 'Warehouse Manager',
            'code' => 'DESH-SOLAR-WAREHOUSE-MANAGER',
            'description' => 'Manages warehouse operations.',
            'is_system' => false,
            'is_active' => true,
        ]);
    }

    private function createPermission(
        string $name,
        string $code,
        string $action
    ): Permission {
        return Permission::create([
            'name' => $name,
            'code' => $code,
            'module' => 'warehouse',
            'action' => $action,
            'is_system' => true,
            'is_active' => true,
        ]);
    }

    public function test_permission_can_be_assigned_to_role(): void
    {
        $company = $this->createCompany();
        $role = $this->createRole($company);

        $permission = $this->createPermission(
            'View Warehouses',
            'warehouse.view',
            'view'
        );

        $role->assignPermission($permission);

        $this->assertDatabaseHas('role_permission', [
            'role_id' => $role->id,
            'permission_id' => $permission->id,
        ]);

        $this->assertTrue(
            $role->permissions->contains($permission)
        );

        $this->assertTrue(
            $permission->roles->contains($role)
        );
    }

    public function test_role_can_have_multiple_permissions(): void
    {
        $company = $this->createCompany();
        $role = $this->createRole($company);

        $viewPermission = $this->createPermission(
            'View Warehouses',
            'warehouse.view',
            'view'
        );

        $managePermission = $this->createPermission(
            'Manage Warehouses',
            'warehouse.manage',
            'manage'
        );

        $role->permissions()->sync([
            $viewPermission->id,
            $managePermission->id,
        ]);

        $this->assertCount(
            2,
            $role->fresh()->permissions
        );

        $this->assertTrue(
            $role->fresh()->hasPermission('warehouse.view')
        );

        $this->assertTrue(
            $role->fresh()->hasPermission('warehouse.manage')
        );
    }

    public function test_assigning_same_permission_twice_does_not_duplicate_it(): void
    {
        $company = $this->createCompany();
        $role = $this->createRole($company);

        $permission = $this->createPermission(
            'View Warehouses',
            'warehouse.view',
            'view'
        );

        $role->assignPermission($permission);
        $role->assignPermission($permission);

        $this->assertDatabaseCount(
            'role_permission',
            1
        );
    }

    public function test_permission_can_be_revoked_from_role(): void
    {
        $company = $this->createCompany();
        $role = $this->createRole($company);

        $permission = $this->createPermission(
            'View Warehouses',
            'warehouse.view',
            'view'
        );

        $role->assignPermission($permission);

        $this->assertTrue(
            $role->hasPermission('warehouse.view')
        );

        $role->revokePermission($permission);

        $this->assertFalse(
            $role->hasPermission('warehouse.view')
        );

        $this->assertDatabaseMissing('role_permission', [
            'role_id' => $role->id,
            'permission_id' => $permission->id,
        ]);
    }

    public function test_inactive_permission_is_not_granted(): void
    {
        $company = $this->createCompany();
        $role = $this->createRole($company);

        $permission = $this->createPermission(
            'View Warehouses',
            'warehouse.view',
            'view'
        );

        $role->assignPermission($permission);

        $permission->update([
            'is_active' => false,
        ]);

        $this->assertFalse(
            $role->hasPermission('warehouse.view')
        );
    }

    public function test_inactive_role_does_not_grant_permission(): void
    {
        $company = $this->createCompany();
        $role = $this->createRole($company);

        $permission = $this->createPermission(
            'View Warehouses',
            'warehouse.view',
            'view'
        );

        $role->assignPermission($permission);

        $role->update([
            'is_active' => false,
        ]);

        $this->assertFalse(
            $role->hasPermission('warehouse.view')
        );
    }
}