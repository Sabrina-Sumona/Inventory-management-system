<?php

namespace Tests\Feature\Api;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WarehouseManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    private function deshSolar(): Company
    {
        return Company::where(
            'code',
            'DESH-SOLAR'
        )->firstOrFail();
    }

    private function headOffice(): Branch
    {
        return Branch::where(
            'code',
            'HEAD-OFFICE'
        )->firstOrFail();
    }

    private function mainWarehouse(): Warehouse
    {
        return Warehouse::where(
            'code',
            'MAIN-WAREHOUSE'
        )->firstOrFail();
    }

    private function companyAdmin(): User
    {
        return User::where(
            'email',
            'admin@deshsolar.com'
        )->firstOrFail();
    }

    private function createBranch(
        string $name = 'Dhaka Branch',
        string $code = 'DHAKA-BRANCH'
    ): Branch {
        return Branch::create([
            'company_id' => $this->deshSolar()->id,
            'name' => $name,
            'code' => $code,
            'email' => null,
            'phone' => null,
            'address' => null,
            'city' => 'Dhaka',
            'district' => 'Dhaka',
            'postal_code' => '1205',
            'is_head_office' => false,
            'is_active' => true,
        ]);
    }

    private function createWarehouse(
        ?Branch $branch = null,
        string $name = 'Dhaka Warehouse',
        string $code = 'DHAKA-WAREHOUSE'
    ): Warehouse {
        $branch ??= $this->headOffice();

        return Warehouse::create([
            'company_id' => $this->deshSolar()->id,
            'branch_id' => $branch->id,
            'name' => $name,
            'code' => $code,
            'email' => null,
            'phone' => null,
            'address' => null,
            'city' => 'Dhaka',
            'district' => 'Dhaka',
            'postal_code' => '1205',
            'is_primary' => false,
            'is_active' => true,
        ]);
    }

    public function test_guest_cannot_access_warehouses(): void
    {
        $this->getJson('/api/warehouses')
            ->assertUnauthorized();
    }

    public function test_company_admin_can_list_accessible_warehouses(): void
    {
        Sanctum::actingAs(
            $this->companyAdmin()
        );

        $this->getJson('/api/warehouses')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'data.warehouses.0.code',
                'MAIN-WAREHOUSE'
            )
            ->assertJsonPath(
                'data.pagination.total',
                1
            );
    }

    public function test_company_admin_can_create_warehouse(): void
    {
        $admin = $this->companyAdmin();
        $branch = $this->headOffice();

        Sanctum::actingAs($admin);

        $response = $this->postJson(
            '/api/warehouses',
            [
                'branch_id' => $branch->id,
                'name' => 'Dhaka Warehouse',
                'code' => 'dhaka-warehouse',
                'email' => 'warehouse@deshsolar.com',
                'phone' => '+8801700000000',
                'address' => 'Dhaka, Bangladesh',
                'city' => 'Dhaka',
                'district' => 'Dhaka',
                'postal_code' => '1205',
                'is_primary' => false,
                'is_active' => true,
            ]
        );

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'data.warehouse.code',
                'DHAKA-WAREHOUSE'
            )
            ->assertJsonPath(
                'data.warehouse.branch.code',
                'HEAD-OFFICE'
            )
            ->assertJsonPath(
                'data.warehouse.company.code',
                'DESH-SOLAR'
            );

        $this->assertDatabaseHas('warehouses', [
            'company_id' => $this->deshSolar()->id,
            'branch_id' => $branch->id,
            'name' => 'Dhaka Warehouse',
            'code' => 'DHAKA-WAREHOUSE',
            'is_active' => true,
        ]);

        $warehouse = Warehouse::where(
            'code',
            'DHAKA-WAREHOUSE'
        )->firstOrFail();

        $this->assertTrue(
            $admin->fresh()
                ->canAccessWarehouse($warehouse)
        );
    }

    public function test_warehouse_creation_validates_required_fields(): void
    {
        Sanctum::actingAs(
            $this->companyAdmin()
        );

        $this->postJson('/api/warehouses', [
            'name' => '',
            'code' => 'invalid code',
            'email' => 'invalid-email',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'branch_id',
                'name',
                'code',
                'email',
            ]);
    }

    public function test_warehouse_code_must_be_unique_inside_company(): void
    {
        Sanctum::actingAs(
            $this->companyAdmin()
        );

        $this->postJson('/api/warehouses', [
            'branch_id' => $this->headOffice()->id,
            'name' => 'Duplicate Main Warehouse',
            'code' => 'MAIN-WAREHOUSE',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'code',
            ]);
    }

    public function test_primary_warehouse_must_be_active(): void
    {
        Sanctum::actingAs(
            $this->companyAdmin()
        );

        $this->postJson('/api/warehouses', [
            'branch_id' => $this->headOffice()->id,
            'name' => 'Inactive Primary Warehouse',
            'code' => 'INACTIVE-PRIMARY',
            'is_primary' => true,
            'is_active' => false,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'is_active',
            ]);
    }

    public function test_user_cannot_create_warehouse_in_unassigned_branch(): void
    {
        $branch = $this->createBranch(
            'Chattogram Branch',
            'CHATTOGRAM-BRANCH'
        );

        Sanctum::actingAs(
            $this->companyAdmin()
        );

        $this->postJson('/api/warehouses', [
            'branch_id' => $branch->id,
            'name' => 'Chattogram Warehouse',
            'code' => 'CHATTOGRAM-WAREHOUSE',
            'is_primary' => false,
            'is_active' => true,
        ])->assertForbidden();
    }

    public function test_company_admin_can_view_assigned_warehouse(): void
    {
        $warehouse = $this->mainWarehouse();

        Sanctum::actingAs(
            $this->companyAdmin()
        );

        $this->getJson(
            "/api/warehouses/{$warehouse->id}"
        )
            ->assertOk()
            ->assertJsonPath(
                'data.warehouse.code',
                'MAIN-WAREHOUSE'
            )
            ->assertJsonPath(
                'data.warehouse.is_primary',
                true
            );
    }

    public function test_company_admin_can_update_assigned_warehouse(): void
    {
        $warehouse = $this->mainWarehouse();

        Sanctum::actingAs(
            $this->companyAdmin()
        );

        $this->patchJson(
            "/api/warehouses/{$warehouse->id}",
            [
                'name' => 'Desh Solar Central Warehouse',
                'email' => 'central@deshsolar.com',
                'phone' => '+8801800000000',
                'city' => 'Dhaka',
                'district' => 'Dhaka',
                'postal_code' => '1212',
            ]
        )
            ->assertOk()
            ->assertJsonPath(
                'data.warehouse.name',
                'Desh Solar Central Warehouse'
            )
            ->assertJsonPath(
                'data.warehouse.email',
                'central@deshsolar.com'
            );

        $this->assertDatabaseHas('warehouses', [
            'id' => $warehouse->id,
            'name' => 'Desh Solar Central Warehouse',
            'email' => 'central@deshsolar.com',
            'city' => 'Dhaka',
        ]);
    }

    public function test_company_admin_can_move_warehouse_to_another_assigned_branch(): void
    {
        $admin = $this->companyAdmin();

        $branch = $this->createBranch(
            'Chattogram Branch',
            'CHATTOGRAM-BRANCH'
        );

        $admin->assignBranch(
            branch: $branch,
            assignedBy: $admin,
        );

        $warehouse = $this->createWarehouse();

        $admin->assignWarehouse(
            warehouse: $warehouse,
            assignedBy: $admin,
        );

        Sanctum::actingAs($admin);

        $this->patchJson(
            "/api/warehouses/{$warehouse->id}",
            [
                'branch_id' => $branch->id,
            ]
        )
            ->assertOk()
            ->assertJsonPath(
                'data.warehouse.branch.code',
                'CHATTOGRAM-BRANCH'
            );

        $this->assertDatabaseHas('warehouses', [
            'id' => $warehouse->id,
            'branch_id' => $branch->id,
        ]);
    }

    public function test_search_and_filters_return_matching_warehouses(): void
    {
        $admin = $this->companyAdmin();

        $warehouse = $this->createWarehouse(
            name: 'Chattogram Solar Warehouse',
            code: 'CHATTOGRAM-WAREHOUSE'
        );

        $admin->assignWarehouse(
            warehouse: $warehouse,
            assignedBy: $admin,
        );

        Sanctum::actingAs($admin);

        $this->getJson(
            '/api/warehouses'
            . '?search=chattogram'
            . '&branch_id=' . $warehouse->branch_id
            . '&is_active=1'
            . '&is_primary=0'
            . '&sort_by=name'
            . '&sort_direction=asc'
            . '&per_page=10'
        )
            ->assertOk()
            ->assertJsonCount(
                1,
                'data.warehouses'
            )
            ->assertJsonPath(
                'data.warehouses.0.code',
                'CHATTOGRAM-WAREHOUSE'
            )
            ->assertJsonPath(
                'data.pagination.total',
                1
            );
    }

    public function test_setting_new_primary_warehouse_removes_old_primary_status(): void
    {
        $admin = $this->companyAdmin();

        $warehouse = $this->createWarehouse();

        $admin->assignWarehouse(
            warehouse: $warehouse,
            assignedBy: $admin,
        );

        Sanctum::actingAs($admin);

        $this->patchJson(
            "/api/warehouses/{$warehouse->id}",
            [
                'is_primary' => true,
                'is_active' => true,
            ]
        )
            ->assertOk()
            ->assertJsonPath(
                'data.warehouse.is_primary',
                true
            );

        $this->assertDatabaseHas('warehouses', [
            'id' => $warehouse->id,
            'is_primary' => true,
        ]);

        $this->assertDatabaseHas('warehouses', [
            'id' => $this->mainWarehouse()->id,
            'is_primary' => false,
        ]);
    }

    public function test_viewer_can_view_but_cannot_create_or_update_warehouse(): void
    {
        $company = $this->deshSolar();
        $branch = $this->headOffice();
        $warehouse = $this->mainWarehouse();

        $viewerRole = Role::where(
            'code',
            'DESH-SOLAR-VIEWER'
        )->firstOrFail();

        $viewer = User::factory()->create([
            'company_id' => $company->id,
        ]);

        $viewer->assignRole($viewerRole);
        $viewer->assignBranch($branch);
        $viewer->assignWarehouse($warehouse);

        Sanctum::actingAs($viewer);

        $this->getJson(
            "/api/warehouses/{$warehouse->id}"
        )->assertOk();

        $this->postJson('/api/warehouses', [
            'branch_id' => $branch->id,
            'name' => 'Unauthorized Warehouse',
            'code' => 'UNAUTHORIZED-WAREHOUSE',
        ])->assertForbidden();

        $this->patchJson(
            "/api/warehouses/{$warehouse->id}",
            [
                'name' => 'Unauthorized Update',
            ]
        )->assertForbidden();
    }

    public function test_user_cannot_access_unassigned_warehouse(): void
    {
        $company = $this->deshSolar();
        $branch = $this->headOffice();
        $warehouse = $this->createWarehouse();

        $viewerRole = Role::where(
            'code',
            'DESH-SOLAR-VIEWER'
        )->firstOrFail();

        $viewer = User::factory()->create([
            'company_id' => $company->id,
        ]);

        $viewer->assignRole($viewerRole);
        $viewer->assignBranch($branch);

        Sanctum::actingAs($viewer);

        $this->getJson(
            "/api/warehouses/{$warehouse->id}"
        )->assertForbidden();
    }

    public function test_company_admin_cannot_access_warehouse_from_another_company(): void
    {
        $otherCompany = Company::create([
            'name' => 'Other Company',
            'code' => 'OTHER-COMPANY',
            'email' => 'info@other.test',
            'timezone' => 'Asia/Dhaka',
            'currency' => 'BDT',
            'is_active' => true,
        ]);

        $otherBranch = Branch::create([
            'company_id' => $otherCompany->id,
            'name' => 'Other Branch',
            'code' => 'OTHER-BRANCH',
            'is_head_office' => false,
            'is_active' => true,
        ]);

        $otherWarehouse = Warehouse::create([
            'company_id' => $otherCompany->id,
            'branch_id' => $otherBranch->id,
            'name' => 'Other Warehouse',
            'code' => 'OTHER-WAREHOUSE',
            'is_primary' => false,
            'is_active' => true,
        ]);

        Sanctum::actingAs(
            $this->companyAdmin()
        );

        $this->getJson(
            "/api/warehouses/{$otherWarehouse->id}"
        )->assertForbidden();
    }

    public function test_non_primary_empty_warehouse_can_be_soft_deleted(): void
    {
        $admin = $this->companyAdmin();
        $warehouse = $this->createWarehouse();

        $admin->assignWarehouse(
            warehouse: $warehouse,
            assignedBy: $admin,
        );

        Sanctum::actingAs($admin);

        $this->deleteJson(
            "/api/warehouses/{$warehouse->id}"
        )
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted($warehouse);
    }

    public function test_primary_warehouse_cannot_be_deleted(): void
    {
        $warehouse = $this->mainWarehouse();

        Sanctum::actingAs(
            $this->companyAdmin()
        );

        $this->deleteJson(
            "/api/warehouses/{$warehouse->id}"
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'warehouse',
            ]);

        $this->assertNotSoftDeleted($warehouse);
    }

    public function test_company_admin_can_restore_deleted_warehouse(): void
    {
        $admin = $this->companyAdmin();
        $warehouse = $this->createWarehouse();

        $admin->assignWarehouse(
            warehouse: $warehouse,
            assignedBy: $admin,
        );

        $warehouse->delete();

        $this->assertSoftDeleted($warehouse);

        Sanctum::actingAs($admin);

        $this->postJson(
            "/api/warehouses/{$warehouse->id}/restore"
        )
            ->assertOk()
            ->assertJsonPath(
                'data.warehouse.code',
                'DHAKA-WAREHOUSE'
            );

        $this->assertNotSoftDeleted(
            $warehouse->fresh()
        );
    }
}