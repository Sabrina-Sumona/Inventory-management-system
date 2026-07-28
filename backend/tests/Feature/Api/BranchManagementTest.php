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

class BranchManagementTest extends TestCase
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

    public function test_guest_cannot_access_branches(): void
    {
        $this->getJson('/api/branches')
            ->assertUnauthorized();
    }

    public function test_company_admin_can_list_accessible_branches(): void
    {
        $admin = $this->companyAdmin();

        Sanctum::actingAs($admin);

        $this->getJson('/api/branches')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'data.branches.0.code',
                'HEAD-OFFICE'
            )
            ->assertJsonPath(
                'data.pagination.total',
                1
            );
    }

    public function test_company_admin_can_create_branch(): void
    {
        $admin = $this->companyAdmin();

        Sanctum::actingAs($admin);

        $response = $this->postJson(
            '/api/branches',
            [
                'name' => 'Chattogram Branch',
                'code' => 'chattogram-branch',
                'email' => 'ctg@deshsolar.com',
                'phone' => '+8801700000000',
                'address' => 'Agrabad',
                'city' => 'Chattogram',
                'district' => 'Chattogram',
                'postal_code' => '4100',
                'is_head_office' => false,
                'is_active' => true,
            ]
        );

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'data.branch.code',
                'CHATTOGRAM-BRANCH'
            )
            ->assertJsonPath(
                'data.branch.company.code',
                'DESH-SOLAR'
            );

        $this->assertDatabaseHas('branches', [
            'company_id' => $this->deshSolar()->id,
            'name' => 'Chattogram Branch',
            'code' => 'CHATTOGRAM-BRANCH',
            'city' => 'Chattogram',
            'is_active' => true,
        ]);

        $branch = Branch::where(
            'code',
            'CHATTOGRAM-BRANCH'
        )->firstOrFail();

        $this->assertTrue(
            $admin->fresh()->canAccessBranch($branch)
        );
    }

    public function test_branch_creation_validates_required_fields(): void
    {
        Sanctum::actingAs(
            $this->companyAdmin()
        );

        $this->postJson(
            '/api/branches',
            [
                'name' => '',
                'code' => 'invalid code',
                'email' => 'invalid-email',
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'name',
                'code',
                'email',
            ]);
    }

    public function test_branch_code_must_be_unique_inside_company(): void
    {
        Sanctum::actingAs(
            $this->companyAdmin()
        );

        $this->postJson(
            '/api/branches',
            [
                'name' => 'Duplicate Head Office',
                'code' => 'HEAD-OFFICE',
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'code',
            ]);
    }

    public function test_company_admin_can_view_assigned_branch(): void
    {
        $branch = $this->headOffice();

        Sanctum::actingAs(
            $this->companyAdmin()
        );

        $this->getJson(
            "/api/branches/{$branch->id}"
        )
            ->assertOk()
            ->assertJsonPath(
                'data.branch.code',
                'HEAD-OFFICE'
            )
            ->assertJsonPath(
                'data.branch.is_head_office',
                true
            );
    }

    public function test_company_admin_can_update_assigned_branch(): void
    {
        $branch = $this->headOffice();

        Sanctum::actingAs(
            $this->companyAdmin()
        );

        $this->patchJson(
            "/api/branches/{$branch->id}",
            [
                'name' => 'Desh Solar Corporate Office',
                'email' => 'office@deshsolar.com',
                'phone' => '+8801800000000',
                'city' => 'Dhaka',
                'district' => 'Dhaka',
                'postal_code' => '1212',
            ]
        )
            ->assertOk()
            ->assertJsonPath(
                'data.branch.name',
                'Desh Solar Corporate Office'
            )
            ->assertJsonPath(
                'data.branch.email',
                'office@deshsolar.com'
            );

        $this->assertDatabaseHas('branches', [
            'id' => $branch->id,
            'name' => 'Desh Solar Corporate Office',
            'email' => 'office@deshsolar.com',
            'city' => 'Dhaka',
        ]);
    }

    public function test_search_and_filters_return_matching_branches(): void
    {
        $admin = $this->companyAdmin();

        $branch = $this->createBranch(
            'Chattogram Solar Branch',
            'CHATTOGRAM-BRANCH'
        );

        $admin->assignBranch(
            branch: $branch,
            assignedBy: $admin,
        );

        Sanctum::actingAs($admin);

        $this->getJson(
            '/api/branches'
            . '?search=chattogram'
            . '&is_active=1'
            . '&sort_by=name'
            . '&sort_direction=asc'
            . '&per_page=10'
        )
            ->assertOk()
            ->assertJsonCount(
                1,
                'data.branches'
            )
            ->assertJsonPath(
                'data.branches.0.code',
                'CHATTOGRAM-BRANCH'
            )
            ->assertJsonPath(
                'data.pagination.total',
                1
            );
    }

    public function test_viewer_can_view_but_cannot_create_or_update_branch(): void
    {
        $company = $this->deshSolar();
        $branch = $this->headOffice();

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
            "/api/branches/{$branch->id}"
        )->assertOk();

        $this->postJson(
            '/api/branches',
            [
                'name' => 'Unauthorized Branch',
                'code' => 'UNAUTHORIZED-BRANCH',
            ]
        )->assertForbidden();

        $this->patchJson(
            "/api/branches/{$branch->id}",
            [
                'name' => 'Unauthorized Update',
            ]
        )->assertForbidden();
    }

    public function test_user_cannot_access_unassigned_branch(): void
    {
        $company = $this->deshSolar();
        $branch = $this->createBranch();

        $viewerRole = Role::where(
            'code',
            'DESH-SOLAR-VIEWER'
        )->firstOrFail();

        $viewer = User::factory()->create([
            'company_id' => $company->id,
        ]);

        $viewer->assignRole($viewerRole);
        $viewer->assignBranch(
            $this->headOffice()
        );

        Sanctum::actingAs($viewer);

        $this->getJson(
            "/api/branches/{$branch->id}"
        )->assertForbidden();
    }

    public function test_company_admin_cannot_access_branch_from_another_company(): void
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

        Sanctum::actingAs(
            $this->companyAdmin()
        );

        $this->getJson(
            "/api/branches/{$otherBranch->id}"
        )->assertForbidden();
    }

    public function test_non_head_office_branch_can_be_soft_deleted(): void
    {
        $admin = $this->companyAdmin();
        $branch = $this->createBranch();

        $admin->assignBranch(
            branch: $branch,
            assignedBy: $admin,
        );

        Sanctum::actingAs($admin);

        $this->deleteJson(
            "/api/branches/{$branch->id}"
        )
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted($branch);
    }

    public function test_head_office_branch_cannot_be_deleted(): void
    {
        $branch = $this->headOffice();

        Sanctum::actingAs(
            $this->companyAdmin()
        );

        $this->deleteJson(
            "/api/branches/{$branch->id}"
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'branch',
            ]);

        $this->assertNotSoftDeleted($branch);
    }

    public function test_branch_with_warehouse_cannot_be_deleted(): void
    {
        $admin = $this->companyAdmin();
        $branch = $this->createBranch();

        $admin->assignBranch(
            branch: $branch,
            assignedBy: $admin,
        );

        Warehouse::create([
            'company_id' => $this->deshSolar()->id,
            'branch_id' => $branch->id,
            'name' => 'Branch Warehouse',
            'code' => 'BRANCH-WAREHOUSE',
            'is_primary' => false,
            'is_active' => true,
        ]);

        Sanctum::actingAs($admin);

        $this->deleteJson(
            "/api/branches/{$branch->id}"
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'branch',
            ]);

        $this->assertNotSoftDeleted($branch);
    }

    public function test_company_admin_can_restore_deleted_branch(): void
    {
        $admin = $this->companyAdmin();
        $branch = $this->createBranch();

        $admin->assignBranch(
            branch: $branch,
            assignedBy: $admin,
        );

        $branch->delete();

        $this->assertSoftDeleted($branch);

        Sanctum::actingAs($admin);

        $this->postJson(
            "/api/branches/{$branch->id}/restore"
        )
            ->assertOk()
            ->assertJsonPath(
                'data.branch.code',
                'DHAKA-BRANCH'
            );

        $this->assertNotSoftDeleted(
            $branch->fresh()
        );
    }
}