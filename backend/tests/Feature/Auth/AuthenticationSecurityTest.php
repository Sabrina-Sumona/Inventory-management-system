<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthenticationSecurityTest extends TestCase
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

    public function test_login_is_rate_limited_after_five_failed_attempts(): void
    {
        $email = 'rate-limit-'.Str::uuid().'@deshsolar.com';

        User::factory()->create([
            'email' => $email,
            'password' => Hash::make('CorrectPassword123!'),
        ]);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->postJson(
                '/api/auth/login',
                [
                    'email' => $email,
                    'password' => 'WrongPassword123!',
                ],
                $this->spaHeaders(),
            )->assertUnprocessable();
        }

        $this->postJson(
            '/api/auth/login',
            [
                'email' => $email,
                'password' => 'WrongPassword123!',
            ],
            $this->spaHeaders(),
        )->assertStatus(429);
    }

    public function test_password_reset_link_requests_are_rate_limited(): void
    {
        $email = 'unknown-'.Str::uuid().'@deshsolar.com';

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $this->postJson(
                '/api/auth/forgot-password',
                [
                    'email' => $email,
                ],
                $this->spaHeaders(),
            )->assertOk();
        }

        $this->postJson(
            '/api/auth/forgot-password',
            [
                'email' => $email,
            ],
            $this->spaHeaders(),
        )->assertStatus(429);
    }

    public function test_guest_cannot_access_protected_authentication_routes(): void
    {
        $this->getJson(
            '/api/auth/user',
            $this->spaHeaders(),
        )->assertUnauthorized();
    }

    public function test_authenticated_user_response_excludes_sensitive_fields(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'web');

        $response = $this->getJson(
            '/api/auth/user',
            $this->spaHeaders(),
        );

        $response->assertOk();

        $userData = $response->json('data.user');

        $this->assertArrayNotHasKey('password', $userData);
        $this->assertArrayNotHasKey('remember_token', $userData);
    }
}