<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class CompanyAccessControlTest extends TestCase
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

    public function test_company_user_can_access_only_own_company(): void
    {
        $deshSolar = $this->createCompany(
            'Desh Solar',
            'DESH-SOLAR'
        );

        $otherCompany = $this->createCompany(
            'Other Company',
            'OTHER-COMPANY'
        );

        $user = User::factory()->create([
            'company_id' => $deshSolar->id,
        ]);

        $this->assertTrue(
            $user->canAccessCompany($deshSolar)
        );

        $this->assertFalse(
            $user->canAccessCompany($otherCompany)
        );

        $accessibleIds = Company::accessibleTo($user)
            ->pluck('id')
            ->all();

        $this->assertSame(
            [$deshSolar->id],
            $accessibleIds
        );
    }

    public function test_super_admin_can_access_every_company(): void
    {
        $firstCompany = $this->createCompany(
            'First Company',
            'FIRST-COMPANY'
        );

        $secondCompany = $this->createCompany(
            'Second Company',
            'SECOND-COMPANY'
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
            $user->canAccessCompany($firstCompany)
        );

        $this->assertTrue(
            $user->canAccessCompany($secondCompany)
        );

        $this->assertCount(
            2,
            Company::accessibleTo($user)->get()
        );
    }

    public function test_user_cannot_receive_role_from_another_company(): void
    {
        $deshSolar = $this->createCompany(
            'Desh Solar',
            'DESH-SOLAR'
        );

        $otherCompany = $this->createCompany(
            'Other Company',
            'OTHER-COMPANY'
        );

        $user = User::factory()->create([
            'company_id' => $deshSolar->id,
        ]);

        $otherCompanyRole = Role::create([
            'company_id' => $otherCompany->id,
            'name' => 'Company Admin',
            'code' => 'OTHER-COMPANY-ADMIN',
            'is_system' => false,
            'is_active' => true,
        ]);

        $this->expectException(
            InvalidArgumentException::class
        );

        $user->assignRole($otherCompanyRole);
    }

    public function test_seeded_admin_belongs_to_desh_solar(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::where(
            'email',
            'admin@deshsolar.com'
        )->firstOrFail();

        $this->assertSame(
            'DESH-SOLAR',
            $user->company->code
        );

        $this->assertTrue(
            $user->hasRole(
                'DESH-SOLAR-COMPANY-ADMIN'
            )
        );

        $this->assertTrue(
            $user->canAccessCompany($user->company)
        );
    }
}