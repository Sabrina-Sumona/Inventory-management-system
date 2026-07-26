<?php

namespace Tests\Feature;

use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_permission_can_be_created(): void
    {
        $permission = Permission::create([
            'name' => 'View Warehouses',
            'code' => 'warehouse.view',
            'module' => 'warehouse',
            'action' => 'view',
            'description' => 'Allows users to view warehouse records.',
            'is_system' => true,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('permissions', [
            'code' => 'warehouse.view',
            'module' => 'warehouse',
            'action' => 'view',
        ]);

        $this->assertSame(
            'View Warehouses',
            $permission->name
        );

        $this->assertTrue($permission->is_system);
        $this->assertTrue($permission->is_active);
    }

    public function test_permission_can_be_soft_deleted(): void
    {
        $permission = Permission::create([
            'name' => 'Adjust Inventory',
            'code' => 'inventory.adjust',
            'module' => 'inventory',
            'action' => 'adjust',
            'is_system' => true,
            'is_active' => true,
        ]);

        $permission->delete();

        $this->assertSoftDeleted('permissions', [
            'id' => $permission->id,
        ]);

        $this->assertNull(
            Permission::find($permission->id)
        );

        $this->assertNotNull(
            Permission::withTrashed()->find($permission->id)
        );
    }
}