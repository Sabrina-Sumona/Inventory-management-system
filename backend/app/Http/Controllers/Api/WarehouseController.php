<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Warehouse\StoreWarehouseRequest;
use App\Http\Requests\Warehouse\UpdateWarehouseRequest;
use App\Http\Resources\WarehouseResource;
use App\Models\Branch;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class WarehouseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Warehouse::class);

        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'search' => [
                'nullable',
                'string',
                'max:100',
            ],

            'branch_id' => [
                'nullable',
                'integer',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],

            'is_primary' => [
                'nullable',
                'boolean',
            ],

            'sort_by' => [
                'nullable',
                'in:name,code,city,district,created_at,updated_at',
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

        $warehouses = Warehouse::query()
            ->accessibleTo($user)
            ->with([
                'company',
                'branch',
            ])
            ->withCount('users')
            ->when(
                $search,
                function ($query, string $search): void {
                    $normalizedSearch = '%'
                        . Str::lower($search)
                        . '%';

                    $query->where(
                        function ($searchQuery) use (
                            $normalizedSearch
                        ): void {
                            $searchQuery
                                ->whereRaw(
                                    'LOWER(warehouses.name) LIKE ?',
                                    [$normalizedSearch]
                                )
                                ->orWhereRaw(
                                    'LOWER(warehouses.code) LIKE ?',
                                    [$normalizedSearch]
                                )
                                ->orWhereRaw(
                                    'LOWER(warehouses.email) LIKE ?',
                                    [$normalizedSearch]
                                )
                                ->orWhereRaw(
                                    'LOWER(warehouses.phone) LIKE ?',
                                    [$normalizedSearch]
                                )
                                ->orWhereRaw(
                                    'LOWER(warehouses.city) LIKE ?',
                                    [$normalizedSearch]
                                )
                                ->orWhereRaw(
                                    'LOWER(warehouses.district) LIKE ?',
                                    [$normalizedSearch]
                                );
                        }
                    );
                }
            )
            ->when(
                isset($validated['branch_id']),
                fn ($query) => $query->where(
                    'warehouses.branch_id',
                    $validated['branch_id']
                )
            )
            ->when(
                array_key_exists(
                    'is_active',
                    $validated
                ),
                fn ($query) => $query->where(
                    'warehouses.is_active',
                    $validated['is_active']
                )
            )
            ->when(
                array_key_exists(
                    'is_primary',
                    $validated
                ),
                fn ($query) => $query->where(
                    'warehouses.is_primary',
                    $validated['is_primary']
                )
            )
            ->orderBy(
                "warehouses.{$sortBy}",
                $sortDirection
            )
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'success' => true,
            'message' => 'Warehouses retrieved successfully.',
            'data' => [
                'warehouses' => WarehouseResource::collection(
                    $warehouses->getCollection()
                )->resolve($request),

                'pagination' => [
                    'current_page' => $warehouses->currentPage(),
                    'last_page' => $warehouses->lastPage(),
                    'per_page' => $warehouses->perPage(),
                    'total' => $warehouses->total(),
                    'from' => $warehouses->firstItem(),
                    'to' => $warehouses->lastItem(),
                ],
            ],
        ]);
    }

    public function store(
        StoreWarehouseRequest $request
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validated();

        $branch = Branch::query()
            ->whereKey($validated['branch_id'])
            ->where('company_id', $user->company_id)
            ->firstOrFail();

        if (! $user->canAccessBranch($branch)) {
            abort(
                403,
                'You do not have access to the selected branch.'
            );
        }

        $warehouse = DB::transaction(
            function () use (
                $validated,
                $user
            ): Warehouse {
                $isPrimary = (bool) (
                    $validated['is_primary']
                    ?? false
                );

                if ($isPrimary) {
                    Warehouse::query()
                        ->where(
                            'company_id',
                            $user->company_id
                        )
                        ->update([
                            'is_primary' => false,
                        ]);
                }

                $warehouse = Warehouse::create([
                    ...$validated,
                    'company_id' => $user->company_id,
                    'is_primary' => $isPrimary,
                    'is_active' => $validated[
                        'is_active'
                    ] ?? true,
                ]);

                $user->assignWarehouse(
                    warehouse: $warehouse,
                    isPrimary: $isPrimary,
                    assignedBy: $user,
                );

                return $warehouse;
            }
        );

        $warehouse
            ->load([
                'company',
                'branch',
            ])
            ->loadCount('users');

        return response()->json([
            'success' => true,
            'message' => 'Warehouse created successfully.',
            'data' => [
                'warehouse' => (
                    new WarehouseResource($warehouse)
                )->resolve($request),
            ],
        ], 201);
    }

    public function show(
        Request $request,
        Warehouse $warehouse
    ): JsonResponse {
        Gate::authorize('view', $warehouse);

        $warehouse
            ->load([
                'company',
                'branch',
            ])
            ->loadCount('users');

        return response()->json([
            'success' => true,
            'message' => 'Warehouse retrieved successfully.',
            'data' => [
                'warehouse' => (
                    new WarehouseResource($warehouse)
                )->resolve($request),
            ],
        ]);
    }

    public function update(
        UpdateWarehouseRequest $request,
        Warehouse $warehouse
    ): JsonResponse {
        $validated = $request->validated();

        /** @var User $user */
        $user = $request->user();

        if (isset($validated['branch_id'])) {
            $branch = Branch::query()
                ->whereKey($validated['branch_id'])
                ->where(
                    'company_id',
                    $warehouse->company_id
                )
                ->firstOrFail();

            if (! $user->canAccessBranch($branch)) {
                abort(
                    403,
                    'You do not have access to the selected branch.'
                );
            }
        }

        DB::transaction(
            function () use (
                $validated,
                $warehouse
            ): void {
                if (
                    array_key_exists(
                        'is_primary',
                        $validated
                    )
                    && $validated['is_primary']
                ) {
                    Warehouse::query()
                        ->where(
                            'company_id',
                            $warehouse->company_id
                        )
                        ->whereKeyNot($warehouse->id)
                        ->update([
                            'is_primary' => false,
                        ]);
                }

                $warehouse->update($validated);
            }
        );

        $warehouse
            ->refresh()
            ->load([
                'company',
                'branch',
            ])
            ->loadCount('users');

        return response()->json([
            'success' => true,
            'message' => 'Warehouse updated successfully.',
            'data' => [
                'warehouse' => (
                    new WarehouseResource($warehouse)
                )->resolve($request),
            ],
        ]);
    }

    public function destroy(
        Request $request,
        Warehouse $warehouse
    ): JsonResponse {
        Gate::authorize('delete', $warehouse);

        if ($warehouse->is_primary) {
            throw ValidationException::withMessages([
                'warehouse' => [
                    'The primary warehouse cannot be deleted.',
                ],
            ]);
        }

        $warehouse->delete();

        return response()->json([
            'success' => true,
            'message' => 'Warehouse deleted successfully.',
            'data' => null,
        ]);
    }

    public function restore(
        Request $request,
        int $warehouse
    ): JsonResponse {
        $warehouseModel = Warehouse::onlyTrashed()
            ->findOrFail($warehouse);

        Gate::authorize('restore', $warehouseModel);

        $warehouseModel->restore();

        $warehouseModel
            ->load([
                'company',
                'branch',
            ])
            ->loadCount('users');

        return response()->json([
            'success' => true,
            'message' => 'Warehouse restored successfully.',
            'data' => [
                'warehouse' => (
                    new WarehouseResource(
                        $warehouseModel
                    )
                )->resolve($request),
            ],
        ]);
    }
}