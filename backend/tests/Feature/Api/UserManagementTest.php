<?php

namespace Tests\Feature\Api;

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserManagementTest extends TestCase
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

    private function viewer(): User
    {
        $role = Role::where(
            'code',
            'DESH-SOLAR-VIEWER'
        )->firstOrFail();

        $user = User::factory()->create([
            'company_id' => $this->company()->id,
            'name' => 'Viewer User',
            'email' => 'viewer@deshsolar.test',
        ]);

        $user->assignRole(
            role: $role,
            assignedBy: $this->admin(),
        );

        return $user;
    }

    public function test_guest_cannot_access_users(): void
    {
        $this->getJson('/api/users')
            ->assertUnauthorized();
    }

    public function test_company_admin_can_list_company_users(): void
    {
        $viewer = $this->viewer();

        Sanctum::actingAs($this->admin());

        $this->getJson('/api/users')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'data.pagination.total',
                2
            )
            ->assertJsonFragment([
                'id' => $viewer->id,
                'email' => 'viewer@deshsolar.test',
            ]);
    }

    public function test_company_admin_can_search_users(): void
    {
        $this->viewer();

        User::factory()->create([
            'company_id' => $this->company()->id,
            'name' => 'Storekeeper User',
            'email' => 'storekeeper@deshsolar.test',
        ]);

        Sanctum::actingAs($this->admin());

        $this->getJson(
            '/api/users'
            . '?search=viewer'
            . '&sort_by=name'
            . '&sort_direction=asc'
            . '&per_page=10'
        )
            ->assertOk()
            ->assertJsonCount(
                1,
                'data.users'
            )
            ->assertJsonPath(
                'data.users.0.email',
                'viewer@deshsolar.test'
            )
            ->assertJsonPath(
                'data.pagination.total',
                1
            );
    }

    public function test_company_admin_can_view_user_from_same_company(): void
    {
        $viewer = $this->viewer();

        Sanctum::actingAs($this->admin());

        $this->getJson(
            "/api/users/{$viewer->id}"
        )
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'data.user.id',
                $viewer->id
            )
            ->assertJsonPath(
                'data.user.company.code',
                'DESH-SOLAR'
            )
            ->assertJsonPath(
                'data.user.roles.0.code',
                'DESH-SOLAR-VIEWER'
            );
    }

    public function test_viewer_cannot_list_or_view_company_users(): void
    {
        $viewer = $this->viewer();

        Sanctum::actingAs($viewer);

        $this->getJson('/api/users')
            ->assertForbidden();

        $this->getJson(
            "/api/users/{$this->admin()->id}"
        )->assertForbidden();
    }

    public function test_company_user_cannot_view_user_from_another_company(): void
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
            "/api/users/{$otherUser->id}"
        )->assertForbidden();
    }

    public function test_company_user_list_excludes_other_company_users(): void
    {
        $otherCompany = Company::create([
            'name' => 'Other Company',
            'code' => 'OTHER-COMPANY',
            'email' => 'contact@other-company.test',
            'timezone' => 'Asia/Dhaka',
            'currency' => 'BDT',
            'is_active' => true,
        ]);

        User::factory()->create([
            'company_id' => $otherCompany->id,
            'name' => 'External User',
            'email' => 'external@other-company.test',
        ]);

        Sanctum::actingAs($this->admin());

        $response = $this->getJson('/api/users');

        $response->assertOk();

        $emails = collect(
            $response->json('data.users')
        )->pluck('email');

        $this->assertFalse(
            $emails->contains(
                'external@other-company.test'
            )
        );

        $this->assertTrue(
            $emails->contains(
                'admin@deshsolar.com'
            )
        );
    }

    public function test_user_response_excludes_sensitive_fields(): void
    {
        $viewer = $this->viewer();

        Sanctum::actingAs($this->admin());

        $this->getJson(
            "/api/users/{$viewer->id}"
        )
            ->assertOk()
            ->assertJsonMissingPath(
                'data.user.password'
            )
            ->assertJsonMissingPath(
                'data.user.remember_token'
            );
    }
}