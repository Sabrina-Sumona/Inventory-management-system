<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class WarehouseAccessControlTest extends TestCase
{
    use RefreshDatabase;

    private function createCompany(
        string $name,
        string $code
    ): Company {
        return Company::create([
            'name' => $name,
            'code' => $code,
            'timezone' => 'Asia/Dhaka',
            'currency' => 'BDT',
            'is_active' => true,
        ]);
    }

    private function createBranch(
        Company $company,
        string $name,
        string $code
    ): Branch {
        return Branch::create([
            'company_id' => $company->id,
            'name' => $name,
            'code' => $code,
            'is_head_office' => false,
            'is_active' => true,
        ]);
    }

    private function createWarehouse(
        Company $company,
        Branch $branch,
        string $name,
        string $code
    ): Warehouse {
        return Warehouse::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'name' => $name,
            'code' => $code,
            'is_primary' => false,
            'is_active' => true,
        ]);
    }

    public function test_user_can_access_assigned_warehouse(): void
    {
        $company = $this->createCompany(
            'Desh Solar',
            'DESH-SOLAR'
        );

        $branch = $this->createBranch(
            $company,
            'Dhaka Branch',
            'DHAKA'
        );

        $warehouse = $this->createWarehouse(
            $company,
            $branch,
            'Dhaka Warehouse',
            'DHAKA-WAREHOUSE'
        );

        $user = User::factory()->create([
            'company_id' => $company->id,
        ]);

        $user->assignBranch(
            branch: $branch,
            isPrimary: true,
        );

        $user->assignWarehouse(
            warehouse: $warehouse,
            isPrimary: true,
        );

        $this->assertTrue(
            $user->canAccessWarehouse($warehouse)
        );

        $this->assertTrue(
            $user->primaryWarehouse()?->is($warehouse)
        );
    }

    public function test_user_cannot_access_unassigned_warehouse(): void
    {
        $company = $this->createCompany(
            'Desh Solar',
            'DESH-SOLAR'
        );

        $branch = $this->createBranch(
            $company,
            'Dhaka Branch',
            'DHAKA'
        );

        $assignedWarehouse = $this->createWarehouse(
            $company,
            $branch,
            'Primary Warehouse',
            'PRIMARY-WAREHOUSE'
        );

        $unassignedWarehouse = $this->createWarehouse(
            $company,
            $branch,
            'Secondary Warehouse',
            'SECONDARY-WAREHOUSE'
        );

        $user = User::factory()->create([
            'company_id' => $company->id,
        ]);

        $user->assignBranch($branch);
        $user->assignWarehouse($assignedWarehouse);

        $this->assertTrue(
            $user->canAccessWarehouse($assignedWarehouse)
        );

        $this->assertFalse(
            $user->canAccessWarehouse($unassignedWarehouse)
        );
    }

    public function test_user_must_have_branch_access_before_warehouse_assignment(): void
    {
        $company = $this->createCompany(
            'Desh Solar',
            'DESH-SOLAR'
        );

        $branch = $this->createBranch(
            $company,
            'Dhaka Branch',
            'DHAKA'
        );

        $warehouse = $this->createWarehouse(
            $company,
            $branch,
            'Dhaka Warehouse',
            'DHAKA-WAREHOUSE'
        );

        $user = User::factory()->create([
            'company_id' => $company->id,
        ]);

        $this->expectException(
            InvalidArgumentException::class
        );

        $user->assignWarehouse($warehouse);
    }

    public function test_user_cannot_receive_warehouse_from_another_company(): void
    {
        $deshSolar = $this->createCompany(
            'Desh Solar',
            'DESH-SOLAR'
        );

        $otherCompany = $this->createCompany(
            'Other Company',
            'OTHER-COMPANY'
        );

        $otherBranch = $this->createBranch(
            $otherCompany,
            'Other Branch',
            'OTHER-BRANCH'
        );

        $otherWarehouse = $this->createWarehouse(
            $otherCompany,
            $otherBranch,
            'Other Warehouse',
            'OTHER-WAREHOUSE'
        );

        $user = User::factory()->create([
            'company_id' => $deshSolar->id,
        ]);

        $this->expectException(
            InvalidArgumentException::class
        );

        $user->assignWarehouse($otherWarehouse);
    }

    public function test_accessible_scope_returns_only_assigned_warehouses(): void
    {
        $company = $this->createCompany(
            'Desh Solar',
            'DESH-SOLAR'
        );

        $branch = $this->createBranch(
            $company,
            'Dhaka Branch',
            'DHAKA'
        );

        $assignedWarehouse = $this->createWarehouse(
            $company,
            $branch,
            'Assigned Warehouse',
            'ASSIGNED-WAREHOUSE'
        );

        $unassignedWarehouse = $this->createWarehouse(
            $company,
            $branch,
            'Unassigned Warehouse',
            'UNASSIGNED-WAREHOUSE'
        );

        $user = User::factory()->create([
            'company_id' => $company->id,
        ]);

        $user->assignBranch($branch);
        $user->assignWarehouse($assignedWarehouse);

        $accessibleWarehouseIds = Warehouse::accessibleTo($user)
            ->pluck('id')
            ->all();

        $this->assertSame(
            [$assignedWarehouse->id],
            $accessibleWarehouseIds
        );

        $this->assertNotContains(
            $unassignedWarehouse->id,
            $accessibleWarehouseIds
        );
    }

    public function test_super_admin_can_access_every_warehouse(): void
    {
        $firstCompany = $this->createCompany(
            'First Company',
            'FIRST-COMPANY'
        );

        $secondCompany = $this->createCompany(
            'Second Company',
            'SECOND-COMPANY'
        );

        $firstBranch = $this->createBranch(
            $firstCompany,
            'First Branch',
            'FIRST-BRANCH'
        );

        $secondBranch = $this->createBranch(
            $secondCompany,
            'Second Branch',
            'SECOND-BRANCH'
        );

        $firstWarehouse = $this->createWarehouse(
            $firstCompany,
            $firstBranch,
            'First Warehouse',
            'FIRST-WAREHOUSE'
        );

        $secondWarehouse = $this->createWarehouse(
            $secondCompany,
            $secondBranch,
            'Second Warehouse',
            'SECOND-WAREHOUSE'
        );

        $superAdminRole = Role::create([
            'company_id' => null,
            'name' => 'Super Admin',
            'code' => 'SUPER-ADMIN',
            'is_system' => true,
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'company_id' => null,
        ]);

        $user->assignRole($superAdminRole);

        $this->assertTrue(
            $user->canAccessWarehouse($firstWarehouse)
        );

        $this->assertTrue(
            $user->canAccessWarehouse($secondWarehouse)
        );

        $this->assertCount(
            2,
            Warehouse::accessibleTo($user)->get()
        );
    }

    public function test_seeded_admin_has_main_warehouse_access(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::where(
            'email',
            'admin@deshsolar.com'
        )->firstOrFail();

        $warehouse = Warehouse::where(
            'code',
            'MAIN-WAREHOUSE'
        )->firstOrFail();

        $this->assertTrue(
            $user->canAccessWarehouse($warehouse)
        );

        $this->assertSame(
            'MAIN-WAREHOUSE',
            $user->primaryWarehouse()?->code
        );
    }
}