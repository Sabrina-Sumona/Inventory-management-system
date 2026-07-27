<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class BranchAccessControlTest extends TestCase
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

    public function test_user_can_access_assigned_branch(): void
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

        $user = User::factory()->create([
            'company_id' => $company->id,
        ]);

        $user->assignBranch(
            branch: $branch,
            isPrimary: true,
        );

        $this->assertTrue(
            $user->canAccessBranch($branch)
        );

        $this->assertTrue(
            $user->primaryBranch()?->is($branch)
        );
    }

    public function test_user_cannot_access_unassigned_branch(): void
    {
        $company = $this->createCompany(
            'Desh Solar',
            'DESH-SOLAR'
        );

        $assignedBranch = $this->createBranch(
            $company,
            'Dhaka Branch',
            'DHAKA'
        );

        $unassignedBranch = $this->createBranch(
            $company,
            'Chattogram Branch',
            'CHATTOGRAM'
        );

        $user = User::factory()->create([
            'company_id' => $company->id,
        ]);

        $user->assignBranch($assignedBranch);

        $this->assertTrue(
            $user->canAccessBranch($assignedBranch)
        );

        $this->assertFalse(
            $user->canAccessBranch($unassignedBranch)
        );
    }

    public function test_user_cannot_receive_branch_from_another_company(): void
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

        $user = User::factory()->create([
            'company_id' => $deshSolar->id,
        ]);

        $this->expectException(
            InvalidArgumentException::class
        );

        $user->assignBranch($otherBranch);
    }

    public function test_accessible_scope_returns_only_assigned_branches(): void
    {
        $company = $this->createCompany(
            'Desh Solar',
            'DESH-SOLAR'
        );

        $dhaka = $this->createBranch(
            $company,
            'Dhaka Branch',
            'DHAKA'
        );

        $chattogram = $this->createBranch(
            $company,
            'Chattogram Branch',
            'CHATTOGRAM'
        );

        $user = User::factory()->create([
            'company_id' => $company->id,
        ]);

        $user->assignBranch($dhaka);

        $accessibleBranchIds = Branch::accessibleTo($user)
            ->pluck('id')
            ->all();

        $this->assertSame(
            [$dhaka->id],
            $accessibleBranchIds
        );

        $this->assertNotContains(
            $chattogram->id,
            $accessibleBranchIds
        );
    }

    public function test_super_admin_can_access_every_branch(): void
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
            $user->canAccessBranch($firstBranch)
        );

        $this->assertTrue(
            $user->canAccessBranch($secondBranch)
        );

        $this->assertCount(
            2,
            Branch::accessibleTo($user)->get()
        );
    }

    public function test_seeded_admin_has_head_office_access(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::where(
            'email',
            'admin@deshsolar.com'
        )->firstOrFail();

        $branch = Branch::where(
            'code',
            'HEAD-OFFICE'
        )->firstOrFail();

        $this->assertTrue(
            $user->canAccessBranch($branch)
        );

        $this->assertSame(
            'HEAD-OFFICE',
            $user->primaryBranch()?->code
        );
    }
}