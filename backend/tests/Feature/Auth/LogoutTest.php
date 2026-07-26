<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, string>
     */
    private function spaHeaders(): array
    {
        return [
            'Accept' => 'application/json',
            'Origin' => 'http://localhost:3000',
            'Referer' => 'http://localhost:3000/',
        ];
    }

    public function test_authenticated_user_can_log_out(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'web');

        $response = $this->postJson(
            '/api/auth/logout',
            [],
            $this->spaHeaders(),
        );

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Logged out successfully.',
                'data' => null,
            ]);

        $this->assertGuest('web');
    }

    public function test_guest_cannot_access_logout_endpoint(): void
    {
        $response = $this->postJson(
            '/api/auth/logout',
            [],
            $this->spaHeaders(),
        );

        $response
            ->assertUnauthorized()
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }
}