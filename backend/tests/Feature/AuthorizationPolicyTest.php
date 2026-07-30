<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class AuthorizationPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_admin_can_manage_assigned_warehouse(): void
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
            Gate::forUser($user)->allows(
                'view',
                $warehouse
            )
        );

        $this->assertTrue(
            Gate::forUser($user)->allows(
                'update',
                $warehouse
            )
        );

        $this->assertTrue(
            Gate::forUser($user)->allows(
                'delete',
                $warehouse
            )
        );
    }

    public function test_viewer_can_view_but_cannot_update_warehouse(): void
    {
        $this->seed(DatabaseSeeder::class);

        $company = Company::where(
            'code',
            'DESH-SOLAR'
        )->firstOrFail();

        $branch = Branch::where(
            'code',
            'HEAD-OFFICE'
        )->firstOrFail();

        $warehouse = Warehouse::where(
            'code',
            'MAIN-WAREHOUSE'
        )->firstOrFail();

        $viewerRole = Role::where(
            'code',
            'DESH-SOLAR-VIEWER'
        )->firstOrFail();

        $user = User::factory()->create([
            'company_id' => $company->id,
        ]);

        $user->assignRole($viewerRole);
        $user->assignBranch($branch);
        $user->assignWarehouse($warehouse);

        $this->assertTrue(
            Gate::forUser($user)->allows(
                'view',
                $warehouse
            )
        );

        $this->assertFalse(
            Gate::forUser($user)->allows(
                'update',
                $warehouse
            )
        );

        $this->assertFalse(
            Gate::forUser($user)->allows(
                'delete',
                $warehouse
            )
        );
    }

    public function test_user_cannot_view_unassigned_warehouse(): void
    {
        $this->seed(DatabaseSeeder::class);

        $company = Company::where(
            'code',
            'DESH-SOLAR'
        )->firstOrFail();

        $branch = Branch::where(
            'code',
            'HEAD-OFFICE'
        )->firstOrFail();

        $warehouse = Warehouse::where(
            'code',
            'MAIN-WAREHOUSE'
        )->firstOrFail();

        $viewerRole = Role::where(
            'code',
            'DESH-SOLAR-VIEWER'
        )->firstOrFail();

        $user = User::factory()->create([
            'company_id' => $company->id,
        ]);

        $user->assignRole($viewerRole);
        $user->assignBranch($branch);

        $this->assertFalse(
            Gate::forUser($user)->allows(
                'view',
                $warehouse
            )
        );
    }

    public function test_super_admin_bypasses_model_policies(): void
    {
        $this->seed(DatabaseSeeder::class);

        $superAdminRole = Role::where(
            'code',
            'SUPER-ADMIN'
        )->firstOrFail();

        $user = User::factory()->create([
            'company_id' => null,
        ]);

        $user->assignRole($superAdminRole);

        $warehouse = Warehouse::where(
            'code',
            'MAIN-WAREHOUSE'
        )->firstOrFail();

        $this->assertTrue(
            Gate::forUser($user)->allows(
                'view',
                $warehouse
            )
        );

        $this->assertTrue(
            Gate::forUser($user)->allows(
                'forceDelete',
                $warehouse
            )
        );
    }
}