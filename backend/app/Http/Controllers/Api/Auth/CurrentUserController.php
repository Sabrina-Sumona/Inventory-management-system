<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\Auth\CurrentUserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CurrentUserController extends Controller
{
    public function __invoke(
        Request $request
    ): JsonResponse {
        /** @var User|null $user */
        $user = $request->user();

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

            return response()->json([
                'success' => false,
                'message' =>
                    'Your account has been deactivated. Please contact an administrator.',
                'data' => null,
            ], 403);
        }

        $user->load([
            'company',
            'roles.permissions',
            'branches',
            'warehouses',
        ]);

        return response()->json([
            'success' => true,
            'message' =>
                'Authenticated user retrieved successfully.',
            'data' => [
                'user' =>
                    new CurrentUserResource(
                        $user
                    ),
            ],
        ]);
    }
}