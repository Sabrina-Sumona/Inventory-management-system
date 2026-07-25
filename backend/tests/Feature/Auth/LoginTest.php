<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
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

    public function test_user_can_log_in_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@deshsolar.com',
            'password' => Hash::make('ChangeMe123!'),
        ]);

        $response = $this->postJson(
            '/api/auth/login',
            [
                'email' => 'admin@deshsolar.com',
                'password' => 'ChangeMe123!',
                'remember' => false,
            ],
            $this->spaHeaders(),
        );

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath(
                'data.user.email',
                'admin@deshsolar.com'
            );

        $this->assertAuthenticatedAs($user);
    }

    public function test_user_cannot_log_in_with_invalid_password(): void
    {
        User::factory()->create([
            'email' => 'admin@deshsolar.com',
            'password' => Hash::make('CorrectPassword123!'),
        ]);

        $response = $this->postJson(
            '/api/auth/login',
            [
                'email' => 'admin@deshsolar.com',
                'password' => 'WrongPassword123!',
            ],
            $this->spaHeaders(),
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'email',
            ]);

        $this->assertGuest();
    }

    public function test_login_requires_email_and_password(): void
    {
        $response = $this->postJson(
            '/api/auth/login',
            [],
            $this->spaHeaders(),
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'email',
                'password',
            ]);
    }
}