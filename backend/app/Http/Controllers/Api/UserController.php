<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function index(
        Request $request
    ): JsonResponse {
        Gate::authorize(
            'viewAny',
            User::class
        );

        /** @var User $authenticatedUser */
        $authenticatedUser = $request->user();

        $validated = $request->validate([
            'search' => [
                'nullable',
                'string',
                'max:100',
            ],

            'sort_by' => [
                'nullable',
                'in:name,email,created_at,updated_at',
            ],

            'sort_direction' => [
                'nullable',
                'in:asc,desc',
            ],

            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],

            'page' => [
                'nullable',
                'integer',
                'min:1',
            ],
        ]);

        $search = $validated['search'] ?? null;
        $sortBy = $validated['sort_by'] ?? 'name';
        $sortDirection =
            $validated['sort_direction'] ?? 'asc';
        $perPage = $validated['per_page'] ?? 15;

        $users = User::query()
            ->when(
                ! $authenticatedUser->isSuperAdmin(),
                fn ($query) => $query->where(
                    'company_id',
                    $authenticatedUser->company_id
                )
            )
            ->with([
                'company:id,name,code',
                'roles:id,name,code',
            ])
            ->withCount([
                'branches',
                'warehouses',
            ])
            ->when(
                $search,
                function (
                    $query,
                    string $search
                ): void {
                    $normalizedSearch = '%'
                        . Str::lower($search)
                        . '%';

                    $query->where(
                        function ($searchQuery) use (
                            $normalizedSearch
                        ): void {
                            $searchQuery
                                ->whereRaw(
                                    'LOWER(users.name) LIKE ?',
                                    [$normalizedSearch]
                                )
                                ->orWhereRaw(
                                    'LOWER(users.email) LIKE ?',
                                    [$normalizedSearch]
                                );
                        }
                    );
                }
            )
            ->orderBy(
                "users.{$sortBy}",
                $sortDirection
            )
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'success' => true,
            'message' =>
                'Users retrieved successfully.',
            'data' => [
                'users' => UserResource::collection(
                    $users->getCollection()
                )->resolve($request),

                'pagination' => [
                    'current_page' =>
                        $users->currentPage(),
                    'last_page' =>
                        $users->lastPage(),
                    'per_page' =>
                        $users->perPage(),
                    'total' =>
                        $users->total(),
                    'from' =>
                        $users->firstItem(),
                    'to' =>
                        $users->lastItem(),
                ],
            ],
        ]);
    }

    public function show(
        Request $request,
        User $user
    ): JsonResponse {
        Gate::authorize(
            'view',
            $user
        );

        $user
            ->load([
                'company:id,name,code',
                'roles:id,name,code',
            ])
            ->loadCount([
                'branches',
                'warehouses',
            ]);

        return response()->json([
            'success' => true,
            'message' =>
                'User retrieved successfully.',
            'data' => [
                'user' => (
                    new UserResource($user)
                )->resolve($request),
            ],
        ]);
    }
}