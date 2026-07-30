<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\WarehouseLocation\StoreWarehouseLocationRequest;
use App\Http\Requests\WarehouseLocation\UpdateWarehouseLocationRequest;
use App\Http\Resources\WarehouseLocationResource;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class WarehouseLocationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        Gate::authorize(
            'viewAny',
            WarehouseLocation::class
        );

        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'search' => [
                'nullable',
                'string',
                'max:100',
            ],

            'warehouse_id' => [
                'nullable',
                'integer',
            ],

            'parent_id' => [
                'nullable',
                'integer',
            ],

            'type' => [
                'nullable',
                'in:zone,rack,shelf,bin',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],

            'sort_by' => [
                'nullable',
                'in:name,code,type,capacity,created_at,updated_at',
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

        $locations = WarehouseLocation::query()
            ->accessibleTo($user)
            ->with([
                'company',
                'branch',
                'warehouse',
                'parent',
            ])
            ->withCount('children')
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
                                    'LOWER(warehouse_locations.name) LIKE ?',
                                    [$normalizedSearch]
                                )
                                ->orWhereRaw(
                                    'LOWER(warehouse_locations.code) LIKE ?',
                                    [$normalizedSearch]
                                )
                                ->orWhereRaw(
                                    'LOWER(warehouse_locations.barcode) LIKE ?',
                                    [$normalizedSearch]
                                )
                                ->orWhereRaw(
                                    'LOWER(warehouse_locations.description) LIKE ?',
                                    [$normalizedSearch]
                                );
                        }
                    );
                }
            )
            ->when(
                isset($validated['warehouse_id']),
                fn ($query) => $query->where(
                    'warehouse_locations.warehouse_id',
                    $validated['warehouse_id']
                )
            )
            ->when(
                isset($validated['parent_id']),
                fn ($query) => $query->where(
                    'warehouse_locations.parent_id',
                    $validated['parent_id']
                )
            )
            ->when(
                isset($validated['type']),
                fn ($query) => $query->where(
                    'warehouse_locations.type',
                    $validated['type']
                )
            )
            ->when(
                array_key_exists(
                    'is_active',
                    $validated
                ),
                fn ($query) => $query->where(
                    'warehouse_locations.is_active',
                    $validated['is_active']
                )
            )
            ->orderBy(
                "warehouse_locations.{$sortBy}",
                $sortDirection
            )
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'success' => true,
            'message' => 'Warehouse locations retrieved successfully.',
            'data' => [
                'locations' => WarehouseLocationResource::collection(
                    $locations->getCollection()
                )->resolve($request),

                'pagination' => [
                    'current_page' => $locations->currentPage(),
                    'last_page' => $locations->lastPage(),
                    'per_page' => $locations->perPage(),
                    'total' => $locations->total(),
                    'from' => $locations->firstItem(),
                    'to' => $locations->lastItem(),
                ],
            ],
        ]);
    }

    public function store(
        StoreWarehouseLocationRequest $request
    ): JsonResponse {
        $validated = $request->validated();

        $warehouse = Warehouse::query()
            ->findOrFail($validated['warehouse_id']);

        $location = DB::transaction(
            fn (): WarehouseLocation =>
                WarehouseLocation::create([
                    ...$validated,
                    'company_id' =>
                        $warehouse->company_id,
                    'branch_id' =>
                        $warehouse->branch_id,
                    'is_active' =>
                        $validated['is_active']
                        ?? true,
                ])
        );

        $location
            ->load([
                'company',
                'branch',
                'warehouse',
                'parent',
            ])
            ->loadCount('children');

        return response()->json([
            'success' => true,
            'message' => 'Warehouse location created successfully.',
            'data' => [
                'location' => (
                    new WarehouseLocationResource(
                        $location
                    )
                )->resolve($request),
            ],
        ], 201);
    }

    public function show(
        Request $request,
        WarehouseLocation $warehouseLocation
    ): JsonResponse {
        Gate::authorize(
            'view',
            $warehouseLocation
        );

        $warehouseLocation
            ->load([
                'company',
                'branch',
                'warehouse',
                'parent',
            ])
            ->loadCount('children');

        return response()->json([
            'success' => true,
            'message' => 'Warehouse location retrieved successfully.',
            'data' => [
                'location' => (
                    new WarehouseLocationResource(
                        $warehouseLocation
                    )
                )->resolve($request),
            ],
        ]);
    }

    public function update(
        UpdateWarehouseLocationRequest $request,
        WarehouseLocation $warehouseLocation
    ): JsonResponse {
        $validated = $request->validated();

        $warehouseId =
            $validated['warehouse_id']
            ?? $warehouseLocation->warehouse_id;

        $warehouse = Warehouse::query()
            ->findOrFail($warehouseId);

        DB::transaction(
            function () use (
                $validated,
                $warehouse,
                $warehouseLocation
            ): void {
                $warehouseLocation->update([
                    ...$validated,
                    'company_id' =>
                        $warehouse->company_id,
                    'branch_id' =>
                        $warehouse->branch_id,
                ]);
            }
        );

        $warehouseLocation
            ->refresh()
            ->load([
                'company',
                'branch',
                'warehouse',
                'parent',
            ])
            ->loadCount('children');

        return response()->json([
            'success' => true,
            'message' => 'Warehouse location updated successfully.',
            'data' => [
                'location' => (
                    new WarehouseLocationResource(
                        $warehouseLocation
                    )
                )->resolve($request),
            ],
        ]);
    }

    public function destroy(
        Request $request,
        WarehouseLocation $warehouseLocation
    ): JsonResponse {
        Gate::authorize(
            'delete',
            $warehouseLocation
        );

        if ($warehouseLocation->children()->exists()) {
            throw ValidationException::withMessages([
                'location' => [
                    'A warehouse location containing child locations cannot be deleted.',
                ],
            ]);
        }

        $warehouseLocation->delete();

        return response()->json([
            'success' => true,
            'message' => 'Warehouse location deleted successfully.',
            'data' => null,
        ]);
    }

    public function restore(
        Request $request,
        int $warehouseLocation
    ): JsonResponse {
        $location = WarehouseLocation::onlyTrashed()
            ->findOrFail($warehouseLocation);

        Gate::authorize('restore', $location);

        $location->restore();

        $location
            ->load([
                'company',
                'branch',
                'warehouse',
                'parent',
            ])
            ->loadCount('children');

        return response()->json([
            'success' => true,
            'message' => 'Warehouse location restored successfully.',
            'data' => [
                'location' => (
                    new WarehouseLocationResource(
                        $location
                    )
                )->resolve($request),
            ],
        ]);
    }
}