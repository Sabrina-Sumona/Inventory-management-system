<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\SyncBranchAssignmentsRequest;
use App\Http\Requests\User\SyncWarehouseAssignmentsRequest;
use App\Models\Branch;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class UserAssignmentController extends Controller
{
    public function show(
        Request $request,
        User $user
    ): JsonResponse {
        Gate::authorize(
            'view',
            $user
        );

        $user->load([
            'company:id,name,code',

            'branches' => function ($query): void {
                $query
                    ->select([
                        'branches.id',
                        'branches.company_id',
                        'branches.name',
                        'branches.code',
                        'branches.city',
                        'branches.district',
                        'branches.is_head_office',
                        'branches.is_active',
                    ])
                    ->orderBy('branches.name');
            },

            'warehouses' => function ($query): void {
                $query
                    ->select([
                        'warehouses.id',
                        'warehouses.company_id',
                        'warehouses.branch_id',
                        'warehouses.name',
                        'warehouses.code',
                        'warehouses.city',
                        'warehouses.district',
                        'warehouses.is_primary',
                        'warehouses.is_active',
                    ])
                    ->with([
                        'branch:id,name,code',
                    ])
                    ->orderBy('warehouses.name');
            },
        ]);

        return response()->json([
            'success' => true,
            'message' =>
                'User assignments retrieved successfully.',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,

                    'company' => $user->company
                        ? [
                            'id' => $user->company->id,
                            'name' => $user->company->name,
                            'code' => $user->company->code,
                        ]
                        : null,

                    'branches' => $user->branches
                        ->map(
                            fn (Branch $branch): array => [
                                'id' => $branch->id,
                                'name' => $branch->name,
                                'code' => $branch->code,
                                'city' => $branch->city,
                                'district' => $branch->district,
                                'is_head_office' =>
                                    $branch->is_head_office,
                                'is_active' =>
                                    $branch->is_active,
                                'is_primary' => (bool)
                                    $branch->pivot->is_primary,
                            ]
                        )
                        ->values(),

                    'warehouses' => $user->warehouses
                        ->map(
                            fn (
                                Warehouse $warehouse
                            ): array => [
                                'id' => $warehouse->id,
                                'name' => $warehouse->name,
                                'code' => $warehouse->code,
                                'city' => $warehouse->city,
                                'district' =>
                                    $warehouse->district,
                                'is_primary' => (bool)
                                    $warehouse->pivot->is_primary,
                                'is_active' =>
                                    $warehouse->is_active,

                                'branch' => [
                                    'id' =>
                                        $warehouse->branch->id,
                                    'name' =>
                                        $warehouse->branch->name,
                                    'code' =>
                                        $warehouse->branch->code,
                                ],
                            ]
                        )
                        ->values(),
                ],
            ],
        ]);
    }

    public function syncBranches(
        SyncBranchAssignmentsRequest $request,
        User $user
    ): JsonResponse {
        $validated = $request->validated();

        /** @var User $authenticatedUser */
        $authenticatedUser = $request->user();

        $branchIds = array_values(
            array_unique(
                array_map(
                    'intval',
                    $validated['branch_ids']
                )
            )
        );

        $primaryBranchId = isset(
            $validated['primary_branch_id']
        )
            ? (int) $validated['primary_branch_id']
            : null;

        DB::transaction(
            function () use (
                $user,
                $authenticatedUser,
                $branchIds,
                $primaryBranchId
            ): void {
                $removedBranchIds = $user
                    ->branches()
                    ->whereNotIn(
                        'branches.id',
                        $branchIds
                    )
                    ->pluck('branches.id')
                    ->map(
                        fn ($branchId): int =>
                            (int) $branchId
                    )
                    ->all();

                if ($removedBranchIds !== []) {
                    $warehouseIdsToRemove = Warehouse::query()
                        ->whereIn(
                            'branch_id',
                            $removedBranchIds
                        )
                        ->pluck('id');

                    $user->warehouses()->detach(
                        $warehouseIdsToRemove
                    );
                }

                $syncData = [];

                foreach ($branchIds as $branchId) {
                    $syncData[$branchId] = [
                        'assigned_by' =>
                            $authenticatedUser->id,
                        'is_primary' =>
                            $primaryBranchId === $branchId,
                    ];
                }

                $user->branches()->sync($syncData);
            }
        );

        return $this->show(
            $request,
            $user->fresh()
        );
    }

    public function syncWarehouses(
        SyncWarehouseAssignmentsRequest $request,
        User $user
    ): JsonResponse {
        $validated = $request->validated();

        /** @var User $authenticatedUser */
        $authenticatedUser = $request->user();

        $warehouseIds = array_values(
            array_unique(
                array_map(
                    'intval',
                    $validated['warehouse_ids']
                )
            )
        );

        $primaryWarehouseId = isset(
            $validated['primary_warehouse_id']
        )
            ? (int) $validated['primary_warehouse_id']
            : null;

        DB::transaction(
            function () use (
                $user,
                $authenticatedUser,
                $warehouseIds,
                $primaryWarehouseId
            ): void {
                $syncData = [];

                foreach ($warehouseIds as $warehouseId) {
                    $syncData[$warehouseId] = [
                        'assigned_by' =>
                            $authenticatedUser->id,
                        'is_primary' =>
                            $primaryWarehouseId
                                === $warehouseId,
                    ];
                }

                $user->warehouses()->sync($syncData);
            }
        );

        return $this->show(
            $request,
            $user->fresh()
        );
    }
}