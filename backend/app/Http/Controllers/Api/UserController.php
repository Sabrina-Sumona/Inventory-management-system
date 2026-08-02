<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Resources\UserResource;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

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
        $sortBy =
            $validated['sort_by'] ?? 'name';

        $sortDirection =
            $validated['sort_direction'] ?? 'asc';

        $perPage =
            $validated['per_page'] ?? 15;

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
                    $normalizedSearch =
                        '%'
                        . Str::lower($search)
                        . '%';

                    $query->where(
                        function (
                            $searchQuery
                        ) use (
                            $normalizedSearch
                        ): void {
                            $searchQuery
                                ->whereRaw(
                                    'LOWER(users.name) LIKE ?',
                                    [
                                        $normalizedSearch,
                                    ]
                                )
                                ->orWhereRaw(
                                    'LOWER(users.email) LIKE ?',
                                    [
                                        $normalizedSearch,
                                    ]
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
                'users' =>
                    UserResource::collection(
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

    public function store(
        StoreUserRequest $request
    ): JsonResponse {
        $validated =
            $request->validated();

        /** @var User $authenticatedUser */
        $authenticatedUser =
            $request->user();

        $companyId =
            $this->resolveCompanyId(
                $authenticatedUser,
                $validated['company_id']
                    ?? null
            );

        $roles = Role::query()
            ->whereIn(
                'id',
                $validated['role_ids']
            )
            ->get();

        $this->validateRolesForUser(
            $roles,
            $companyId
        );

        $user = DB::transaction(
            function () use (
                $validated,
                $companyId,
                $roles,
                $authenticatedUser
            ): User {
                $user = User::query()
                    ->create([
                        'company_id' =>
                            $companyId,

                        'name' =>
                            $validated['name'],

                        'email' =>
                            $validated['email'],

                        'password' =>
                            $validated['password'],
                    ]);

                foreach ($roles as $role) {
                    $user->assignRole(
                        $role,
                        $authenticatedUser
                    );
                }

                return $user;
            }
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
                'User created successfully.',

            'data' => [
                'user' => (
                    new UserResource($user)
                )->resolve($request),
            ],
        ], 201);
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

    private function resolveCompanyId(
        User $authenticatedUser,
        mixed $requestedCompanyId
    ): ?int {
        if (
            ! $authenticatedUser->isSuperAdmin()
        ) {
            if (
                $authenticatedUser->company_id
                === null
            ) {
                throw ValidationException::withMessages([
                    'company_id' => [
                        'Your account is not assigned to a company.',
                    ],
                ]);
            }

            if (
                $requestedCompanyId !== null
                && (int) $requestedCompanyId
                    !==
                    (int) $authenticatedUser
                        ->company_id
            ) {
                throw ValidationException::withMessages([
                    'company_id' => [
                        'You cannot create a user for another company.',
                    ],
                ]);
            }

            return (int)
                $authenticatedUser->company_id;
        }

        return $requestedCompanyId !== null
            ? (int) $requestedCompanyId
            : null;
    }

    private function validateRolesForUser(
        $roles,
        ?int $companyId
    ): void {
        foreach ($roles as $role) {
            if (! $role->is_active) {
                throw ValidationException::withMessages([
                    'role_ids' => [
                        "The role {$role->name} is inactive.",
                    ],
                ]);
            }

            if ($companyId === null) {
                if (
                    $role->company_id !== null
                    || ! $role->is_system
                ) {
                    throw ValidationException::withMessages([
                        'role_ids' => [
                            'A global user can only receive an active global system role.',
                        ],
                    ]);
                }

                continue;
            }

            if (
                $role->company_id === null
                || (int) $role->company_id
                    !== $companyId
            ) {
                throw ValidationException::withMessages([
                    'role_ids' => [
                        "The role {$role->name} does not belong to the selected company.",
                    ],
                ]);
            }
        }
    }
}