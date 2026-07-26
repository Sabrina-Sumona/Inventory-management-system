<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_request_a_password_reset_link(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'admin@deshsolar.com',
        ]);

        $response = $this->postJson(
            '/api/auth/forgot-password',
            [
                'email' => $user->email,
            ]
        );

        $response
            ->assertOk()
            ->assertJsonPath('success', true);

        Notification::assertSentTo(
            $user,
            ResetPassword::class
        );
    }

    public function test_unknown_email_receives_generic_response(): void
    {
        Notification::fake();

        $response = $this->postJson(
            '/api/auth/forgot-password',
            [
                'email' => 'unknown@deshsolar.com',
            ]
        );

        $response
            ->assertOk()
            ->assertJsonPath('success', true);

        Notification::assertNothingSent();
    }

    public function test_email_is_required_for_reset_link_request(): void
    {
        $response = $this->postJson(
            '/api/auth/forgot-password',
            []
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'email',
            ]);
    }

    public function test_user_can_reset_password_with_valid_token(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@deshsolar.com',
            'password' => Hash::make('OldPassword123!'),
        ]);

        $token = Password::createToken($user);

        $response = $this->postJson(
            '/api/auth/reset-password',
            [
                'token' => $token,
                'email' => $user->email,
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ]
        );

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Password reset successfully.',
                'data' => null,
            ]);

        $this->assertTrue(
            Hash::check(
                'NewPassword123!',
                $user->fresh()->password
            )
        );
    }

    public function test_invalid_reset_token_is_rejected(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@deshsolar.com',
        ]);

        $response = $this->postJson(
            '/api/auth/reset-password',
            [
                'token' => 'invalid-token',
                'email' => $user->email,
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ]
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'email',
            ]);
    }

    public function test_password_confirmation_must_match(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson(
            '/api/auth/reset-password',
            [
                'token' => 'test-token',
                'email' => $user->email,
                'password' => 'NewPassword123!',
                'password_confirmation' => 'DifferentPassword123!',
            ]
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'password',
            ]);
    }
}