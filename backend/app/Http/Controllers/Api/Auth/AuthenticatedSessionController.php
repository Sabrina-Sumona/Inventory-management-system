<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    public function store(
        LoginRequest $request
    ): JsonResponse {
        $credentials = $request
            ->safe()
            ->only([
                'email',
                'password',
            ]);

        $remember =
            $request->boolean('remember');

        if (
            ! Auth::guard('web')->attempt(
                $credentials,
                $remember
            )
        ) {
            throw ValidationException::withMessages([
                'email' => [
                    'The provided credentials are incorrect.',
                ],
            ]);
        }

        /** @var User|null $user */
        $user = Auth::guard('web')->user();

        if (
            $user === null ||
            $user->is_active === false
        ) {
            Auth::guard('web')->logout();

            if ($request->hasSession()) {
                $request
                    ->session()
                    ->invalidate();

                $request
                    ->session()
                    ->regenerateToken();
            }

            throw ValidationException::withMessages([
                'email' => [
                    'This account has been deactivated. Please contact an administrator.',
                ],
            ]);
        }

        if ($request->hasSession()) {
            $request
                ->session()
                ->regenerate();
        }

        return response()->json([
            'success' => true,
            'message' =>
                'Logged in successfully.',

            'data' => [
                'user' => (
                    new UserResource($user)
                )->resolve($request),
            ],
        ]);
    }

    public function destroy(
        Request $request
    ): JsonResponse {
        Auth::guard('web')->logout();

        if ($request->hasSession()) {
            $request
                ->session()
                ->invalidate();

            $request
                ->session()
                ->regenerateToken();
        }

        return response()->json([
            'success' => true,
            'message' =>
                'Logged out successfully.',
            'data' => null,
        ]);
    }
}