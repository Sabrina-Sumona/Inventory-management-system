<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\Auth\CurrentUserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CurrentUserController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $user->load([
            'company',
            'roles.permissions',
            'branches',
            'warehouses',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Authenticated user retrieved successfully.',
            'data' => [
                'user' => new CurrentUserResource($user),
            ],
        ]);
    }
}