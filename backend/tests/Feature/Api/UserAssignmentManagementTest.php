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

class UserAssignmentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    private function company(): Company
    {
        return Company::where(
            'code',
            'DESH-SOLAR'
        )->firstOrFail();
    }

    private function admin(): User
    {
        return User::where(
            'email',
            'admin@deshsolar.com'
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

    private function targetUser(): User
    {
        $role = Role::where(
            'code',
            'DESH-SOLAR-VIEWER'
        )->firstOrFail();

        $user = User::factory()->create([
            'company_id' => $this->company()->id,
            'name' => 'Assignment Test User',
            'email' => 'assignment.user@deshsolar.test',
        ]);

        $user->assignRole(
            role: $role,
            assignedBy: $this->admin(),
        );

        return $user;
    }

    private function secondBranch(): Branch
    {
        return Branch::create([
            'company_id' => $this->company()->id,
            'name' => 'Chattogram Branch',
            'code' => 'CHATTOGRAM-BRANCH',
            'email' => null,
            'phone' => null,
            'address' => 'Chattogram, Bangladesh',
            'city' => 'Chattogram',
            'district' => 'Chattogram',
            'postal_code' => '4000',
            'is_head_office' => false,
            'is_active' => true,
        ]);
    }

    private function secondWarehouse(
        Branch $branch
    ): Warehouse {
        return Warehouse::create([
            'company_id' => $this->company()->id,
            'branch_id' => $branch->id,
            'name' => 'Chattogram Warehouse',
            'code' => 'CHATTOGRAM-WAREHOUSE',
            'email' => null,
            'phone' => null,
            'address' => 'Chattogram, Bangladesh',
            'city' => 'Chattogram',
            'district' => 'Chattogram',
            'postal_code' => '4000',
            'is_primary' => false,
            'is_active' => true,
        ]);
    }

    public function test_guest_cannot_access_user_assignments(): void
    {
        $user = $this->targetUser();

        $this->getJson(
            "/api/users/{$user->id}/assignments"
        )->assertUnauthorized();
    }

    public function test_company_admin_can_view_user_assignments(): void
    {
        $user = $this->targetUser();
        $branch = $this->headOffice();
        $warehouse = $this->mainWarehouse();

        $user->assignBranch(
            branch: $branch,
            isPrimary: true,
            assignedBy: $this->admin(),
        );

        $user->assignWarehouse(
            warehouse: $warehouse,
            isPrimary: true,
            assignedBy: $this->admin(),
        );

        Sanctum::actingAs($this->admin());

        $this->getJson(
            "/api/users/{$user->id}/assignments"
        )
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'data.user.id',
                $user->id
            )
            ->assertJsonPath(
                'data.user.branches.0.code',
                'HEAD-OFFICE'
            )
            ->assertJsonPath(
                'data.user.branches.0.is_primary',
                true
            )
            ->assertJsonPath(
                'data.user.warehouses.0.code',
                'MAIN-WAREHOUSE'
            )
            ->assertJsonPath(
                'data.user.warehouses.0.is_primary',
                true
            );
    }

    public function test_company_admin_can_sync_branch_assignments(): void
    {
        $user = $this->targetUser();
        $headOffice = $this->headOffice();
        $secondBranch = $this->secondBranch();

        Sanctum::actingAs($this->admin());

        $this->putJson(
            "/api/users/{$user->id}/branch-assignments",
            [
                'branch_ids' => [
                    $headOffice->id,
                    $secondBranch->id,
                ],
                'primary_branch_id' =>
                    $secondBranch->id,
            ]
        )
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(
                2,
                'data.user.branches'
            );

        $this->assertDatabaseHas('branch_user', [
            'user_id' => $user->id,
            'branch_id' => $headOffice->id,
            'is_primary' => false,
        ]);

        $this->assertDatabaseHas('branch_user', [
            'user_id' => $user->id,
            'branch_id' => $secondBranch->id,
            'is_primary' => true,
        ]);
    }

    public function test_primary_branch_must_be_in_selected_branches(): void
    {
        $user = $this->targetUser();
        $headOffice = $this->headOffice();
        $secondBranch = $this->secondBranch();

        Sanctum::actingAs($this->admin());

        $this->putJson(
            "/api/users/{$user->id}/branch-assignments",
            [
                'branch_ids' => [
                    $headOffice->id,
                ],
                'primary_branch_id' =>
                    $secondBranch->id,
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'primary_branch_id',
            ]);
    }

    public function test_company_admin_can_sync_warehouse_assignments(): void
    {
        $user = $this->targetUser();
        $headOffice = $this->headOffice();
        $mainWarehouse = $this->mainWarehouse();

        $secondBranch = $this->secondBranch();
        $secondWarehouse = $this->secondWarehouse(
            $secondBranch
        );

        $user->assignBranch(
            branch: $headOffice,
            assignedBy: $this->admin(),
        );

        $user->assignBranch(
            branch: $secondBranch,
            isPrimary: true,
            assignedBy: $this->admin(),
        );

        Sanctum::actingAs($this->admin());

        $this->putJson(
            "/api/users/{$user->id}/warehouse-assignments",
            [
                'warehouse_ids' => [
                    $mainWarehouse->id,
                    $secondWarehouse->id,
                ],
                'primary_warehouse_id' =>
                    $secondWarehouse->id,
            ]
        )
            ->assertOk()
            ->assertJsonCount(
                2,
                'data.user.warehouses'
            );

        $this->assertDatabaseHas('user_warehouse', [
            'user_id' => $user->id,
            'warehouse_id' => $mainWarehouse->id,
            'is_primary' => false,
        ]);

        $this->assertDatabaseHas('user_warehouse', [
            'user_id' => $user->id,
            'warehouse_id' => $secondWarehouse->id,
            'is_primary' => true,
        ]);
    }

    public function test_user_must_have_branch_before_warehouse_assignment(): void
    {
        $user = $this->targetUser();

        $secondBranch = $this->secondBranch();
        $secondWarehouse = $this->secondWarehouse(
            $secondBranch
        );

        Sanctum::actingAs($this->admin());

        $this->putJson(
            "/api/users/{$user->id}/warehouse-assignments",
            [
                'warehouse_ids' => [
                    $secondWarehouse->id,
                ],
                'primary_warehouse_id' =>
                    $secondWarehouse->id,
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'warehouse_ids',
            ]);
    }

    public function test_removing_branch_also_removes_its_warehouse_assignments(): void
    {
        $user = $this->targetUser();

        $headOffice = $this->headOffice();
        $mainWarehouse = $this->mainWarehouse();

        $secondBranch = $this->secondBranch();
        $secondWarehouse = $this->secondWarehouse(
            $secondBranch
        );

        $user->assignBranch(
            branch: $headOffice,
            isPrimary: true,
            assignedBy: $this->admin(),
        );

        $user->assignBranch(
            branch: $secondBranch,
            assignedBy: $this->admin(),
        );

        $user->assignWarehouse(
            warehouse: $mainWarehouse,
            isPrimary: true,
            assignedBy: $this->admin(),
        );

        $user->assignWarehouse(
            warehouse: $secondWarehouse,
            assignedBy: $this->admin(),
        );

        Sanctum::actingAs($this->admin());

        $this->putJson(
            "/api/users/{$user->id}/branch-assignments",
            [
                'branch_ids' => [
                    $headOffice->id,
                ],
                'primary_branch_id' =>
                    $headOffice->id,
            ]
        )->assertOk();

        $this->assertDatabaseHas('user_warehouse', [
            'user_id' => $user->id,
            'warehouse_id' => $mainWarehouse->id,
        ]);

        $this->assertDatabaseMissing('user_warehouse', [
            'user_id' => $user->id,
            'warehouse_id' => $secondWarehouse->id,
        ]);
    }

    public function test_viewer_cannot_manage_user_assignments(): void
    {
        $targetUser = $this->targetUser();
        $viewer = User::factory()->create([
            'company_id' => $this->company()->id,
        ]);

        $viewerRole = Role::where(
            'code',
            'DESH-SOLAR-VIEWER'
        )->firstOrFail();

        $viewer->assignRole($viewerRole);

        Sanctum::actingAs($viewer);

        $this->putJson(
            "/api/users/{$targetUser->id}/branch-assignments",
            [
                'branch_ids' => [
                    $this->headOffice()->id,
                ],
                'primary_branch_id' =>
                    $this->headOffice()->id,
            ]
        )->assertForbidden();
    }

    public function test_company_admin_cannot_manage_user_from_another_company(): void
    {
        $otherCompany = Company::create([
            'name' => 'Other Company',
            'code' => 'OTHER-COMPANY',
            'email' => 'info@other-company.test',
            'timezone' => 'Asia/Dhaka',
            'currency' => 'BDT',
            'is_active' => true,
        ]);

        $otherUser = User::factory()->create([
            'company_id' => $otherCompany->id,
        ]);

        Sanctum::actingAs($this->admin());

        $this->getJson(
            "/api/users/{$otherUser->id}/assignments"
        )->assertForbidden();

        $this->putJson(
            "/api/users/{$otherUser->id}/branch-assignments",
            [
                'branch_ids' => [],
                'primary_branch_id' => null,
            ]
        )->assertForbidden();
    }
}