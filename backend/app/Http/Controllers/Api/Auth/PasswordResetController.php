<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PasswordResetController extends Controller
{
    public function sendResetLink(
        ForgotPasswordRequest $request
    ): JsonResponse {
        $status = Password::sendResetLink(
            $request->safe()->only('email')
        );

        /*
         * Return the same response for known and unknown email addresses.
         * This prevents exposing whether an account exists.
         */
        if (
            $status === Password::ResetLinkSent ||
            $status === Password::InvalidUser
        ) {
            return response()->json([
                'success' => true,
                'message' => 'If an account exists for that email address, a password reset link has been sent.',
                'data' => null,
            ]);
        }

        if ($status === Password::ResetThrottled) {
            return response()->json([
                'success' => false,
                'message' => 'Please wait before requesting another password reset link.',
                'data' => null,
            ], 429);
        }

        throw ValidationException::withMessages([
            'email' => [
                __($status),
            ],
        ]);
    }

    public function reset(
        ResetPasswordRequest $request
    ): JsonResponse {
        $status = Password::reset(
            $request->safe()->only([
                'email',
                'password',
                'password_confirmation',
                'token',
            ]),
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->setRememberToken(
                    Str::random(60)
                );

                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PasswordReset) {
            throw ValidationException::withMessages([
                'email' => [
                    __($status),
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Password reset successfully.',
            'data' => null,
        ]);
    }
}