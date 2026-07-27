<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CurrentUserContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_receives_full_organization_context(): void
    {
        $this->seed(DatabaseSeeder::class);

        $user = User::where(
            'email',
            'admin@deshsolar.com'
        )->firstOrFail();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/auth/user');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'data.user.email',
                'admin@deshsolar.com'
            )
            ->assertJsonPath(
                'data.user.company.code',
                'DESH-SOLAR'
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
            )
            ->assertJsonFragment([
                'code' => 'DESH-SOLAR-COMPANY-ADMIN',
            ])
            ->assertJsonFragment([
                'code' => 'warehouse.view',
            ]);
    }

    public function test_guest_cannot_access_current_user_context(): void
    {
        $this->getJson('/api/auth/user')
            ->assertUnauthorized();
    }
}