<?php

namespace Tests\Feature\Api;

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CompanyManagementTest extends TestCase
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

    private function companyAdmin(): User
    {
        return User::where(
            'email',
            'admin@deshsolar.com'
        )->firstOrFail();
    }

    public function test_guest_cannot_access_companies(): void
    {
        $this->getJson('/api/companies')
            ->assertUnauthorized();
    }

    public function test_company_admin_can_list_accessible_company(): void
    {
        Sanctum::actingAs(
            $this->companyAdmin()
        );

        $this->getJson('/api/companies')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(
                1,
                'data.companies'
            )
            ->assertJsonPath(
                'data.companies.0.code',
                'DESH-SOLAR'
            );
    }

    public function test_company_admin_can_view_own_company(): void
    {
        $company = $this->deshSolar();

        Sanctum::actingAs(
            $this->companyAdmin()
        );

        $this->getJson(
            "/api/companies/{$company->id}"
        )
            ->assertOk()
            ->assertJsonPath(
                'data.company.name',
                'Desh Solar'
            )
            ->assertJsonPath(
                'data.company.code',
                'DESH-SOLAR'
            );
    }

    public function test_company_admin_can_update_company_information(): void
    {
        $company = $this->deshSolar();

        Sanctum::actingAs(
            $this->companyAdmin()
        );

        $this->patchJson(
            "/api/companies/{$company->id}",
            [
                'name' => 'Desh Solar Limited',
                'email' => 'info@deshsolar.com',
                'website' => 'https://deshsolar.com',
                'phone' => '+8801700000000',
                'address' => 'Dhaka, Bangladesh',
                'timezone' => 'Asia/Dhaka',
                'currency' => 'bdt',
                'code' => 'CHANGED-CODE',
                'is_active' => false,
            ]
        )
            ->assertOk()
            ->assertJsonPath(
                'data.company.name',
                'Desh Solar Limited'
            )
            ->assertJsonPath(
                'data.company.currency',
                'BDT'
            )
            ->assertJsonPath(
                'data.company.code',
                'DESH-SOLAR'
            )
            ->assertJsonPath(
                'data.company.is_active',
                true
            );

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'name' => 'Desh Solar Limited',
            'code' => 'DESH-SOLAR',
            'currency' => 'BDT',
            'is_active' => true,
        ]);
    }

    public function test_viewer_cannot_update_company(): void
    {
        $company = $this->deshSolar();

        $viewerRole = Role::where(
            'code',
            'DESH-SOLAR-VIEWER'
        )->firstOrFail();

        $viewer = User::factory()->create([
            'company_id' => $company->id,
        ]);

        $viewer->assignRole($viewerRole);

        Sanctum::actingAs($viewer);

        $this->patchJson(
            "/api/companies/{$company->id}",
            [
                'name' => 'Unauthorized Change',
            ]
        )->assertForbidden();
    }

    public function test_company_admin_cannot_access_another_company(): void
    {
        $otherCompany = Company::create([
            'name' => 'Other Company',
            'code' => 'OTHER-COMPANY',
            'email' => 'info@other-company.test',
            'timezone' => 'Asia/Dhaka',
            'currency' => 'BDT',
            'is_active' => true,
        ]);

        Sanctum::actingAs(
            $this->companyAdmin()
        );

        $this->getJson(
            "/api/companies/{$otherCompany->id}"
        )->assertForbidden();
    }

    public function test_company_update_validates_input(): void
    {
        $company = $this->deshSolar();

        Sanctum::actingAs(
            $this->companyAdmin()
        );

        $this->patchJson(
            "/api/companies/{$company->id}",
            [
                'name' => '',
                'email' => 'invalid-email',
                'website' => 'not-a-url',
                'currency' => 'TAKA',
                'timezone' => 'Invalid/Timezone',
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'name',
                'email',
                'website',
                'currency',
                'timezone',
            ]);
    }
}