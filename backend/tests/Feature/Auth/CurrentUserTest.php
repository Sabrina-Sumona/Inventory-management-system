<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrentUserTest extends TestCase
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

    public function test_authenticated_user_can_retrieve_their_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'Desh Solar Administrator',
            'email' => 'admin@deshsolar.com',
        ]);

        $this->actingAs($user, 'web');

        $response = $this->getJson(
            '/api/auth/user',
            $this->spaHeaders(),
        );

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'message',
                'Authenticated user retrieved successfully.'
            )
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath(
                'data.user.name',
                'Desh Solar Administrator'
            )
            ->assertJsonPath(
                'data.user.email',
                'admin@deshsolar.com'
            );

        $userData = $response->json('data.user');

        $this->assertArrayNotHasKey('password', $userData);
        $this->assertArrayNotHasKey('remember_token', $userData);
    }

    public function test_guest_cannot_retrieve_authenticated_user(): void
    {
        $response = $this->getJson(
            '/api/auth/user',
            $this->spaHeaders(),
        );

        $response
            ->assertUnauthorized()
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }
}