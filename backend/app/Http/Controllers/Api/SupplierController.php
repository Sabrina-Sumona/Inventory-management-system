<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supplier\StoreSupplierRequest;
use App\Http\Requests\Supplier\UpdateSupplierRequest;
use App\Http\Resources\SupplierResource;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class SupplierController extends Controller
{
    public function index(
        Request $request
    ): JsonResponse {
        Gate::authorize(
            'viewAny',
            Supplier::class
        );

        /** @var User $authenticatedUser */
        $authenticatedUser = $request->user();

        $validated = $request->validate([
            'search' => [
                'nullable',
                'string',
                'max:100',
            ],

            'branch_id' => [
                'nullable',
                'integer',
                'exists:branches,id',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],

            'opening_balance_type' => [
                'nullable',
                'in:payable,receivable',
            ],

            'sort_by' => [
                'nullable',
                'in:name,code,created_at,updated_at',
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
        $branchId = $validated['branch_id'] ?? null;
        $isActive = $validated['is_active'] ?? null;

        $openingBalanceType =
            $validated['opening_balance_type']
                ?? null;

        $sortBy =
            $validated['sort_by'] ?? 'name';

        $sortDirection =
            $validated['sort_direction'] ?? 'asc';

        $perPage =
            $validated['per_page'] ?? 15;

        $suppliers = Supplier::query()
            ->accessibleTo($authenticatedUser)
            ->with([
                'company:id,name,code',

                'branch:id,company_id,name,code,city,district,is_head_office,is_active',

                'creator:id,name,email',

                'updater:id,name,email',
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
                        function ($searchQuery) use (
                            $normalizedSearch
                        ): void {
                            $searchQuery
                                ->whereRaw(
                                    'LOWER(suppliers.name) LIKE ?',
                                    [$normalizedSearch]
                                )
                                ->orWhereRaw(
                                    'LOWER(suppliers.code) LIKE ?',
                                    [$normalizedSearch]
                                )
                                ->orWhereRaw(
                                    'LOWER(COALESCE(suppliers.business_name, \'\')) LIKE ?',
                                    [$normalizedSearch]
                                )
                                ->orWhereRaw(
                                    'LOWER(COALESCE(suppliers.email, \'\')) LIKE ?',
                                    [$normalizedSearch]
                                )
                                ->orWhereRaw(
                                    'LOWER(COALESCE(suppliers.phone, \'\')) LIKE ?',
                                    [$normalizedSearch]
                                );
                        }
                    );
                }
            )
            ->when(
                $branchId !== null,
                fn ($query) => $query->where(
                    'suppliers.branch_id',
                    $branchId
                )
            )
            ->when(
                $isActive !== null,
                fn ($query) => $query->where(
                    'suppliers.is_active',
                    $isActive
                )
            )
            ->when(
                $openingBalanceType !== null,
                fn ($query) => $query->where(
                    'suppliers.opening_balance_type',
                    $openingBalanceType
                )
            )
            ->orderBy(
                "suppliers.{$sortBy}",
                $sortDirection
            )
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'success' => true,
            'message' =>
                'Suppliers retrieved successfully.',
            'data' => [
                'suppliers' =>
                    SupplierResource::collection(
                        $suppliers->getCollection()
                    )->resolve($request),

                'pagination' => [
                    'current_page' =>
                        $suppliers->currentPage(),

                    'last_page' =>
                        $suppliers->lastPage(),

                    'per_page' =>
                        $suppliers->perPage(),

                    'total' =>
                        $suppliers->total(),

                    'from' =>
                        $suppliers->firstItem(),

                    'to' =>
                        $suppliers->lastItem(),
                ],
            ],
        ]);
    }

    public function store(
        StoreSupplierRequest $request
    ): JsonResponse {
        $validated = $request->validated();

        /** @var User $authenticatedUser */
        $authenticatedUser = $request->user();

        $supplier = DB::transaction(
            function () use (
                $validated,
                $authenticatedUser
            ): Supplier {
                return Supplier::create([
                    ...$validated,

                    'country' =>
                        $validated['country']
                            ?? 'Bangladesh',

                    'payment_term_days' =>
                        $validated[
                            'payment_term_days'
                        ] ?? 0,

                    'credit_limit' =>
                        $validated['credit_limit']
                            ?? 0,

                    'opening_balance' =>
                        $validated[
                            'opening_balance'
                        ] ?? 0,

                    'opening_balance_type' =>
                        $validated[
                            'opening_balance_type'
                        ] ?? 'payable',

                    'is_active' =>
                        $validated['is_active']
                            ?? true,

                    'created_by' =>
                        $authenticatedUser->id,

                    'updated_by' =>
                        $authenticatedUser->id,
                ]);
            }
        );

        $supplier->load([
            'company:id,name,code',

            'branch:id,company_id,name,code,city,district,is_head_office,is_active',

            'creator:id,name,email',

            'updater:id,name,email',
        ]);

        return response()->json([
            'success' => true,
            'message' =>
                'Supplier created successfully.',
            'data' => [
                'supplier' => (
                    new SupplierResource($supplier)
                )->resolve($request),
            ],
        ], 201);
    }

    public function show(
        Request $request,
        Supplier $supplier
    ): JsonResponse {
        Gate::authorize(
            'view',
            $supplier
        );

        $supplier->load([
            'company:id,name,code',

            'branch:id,company_id,name,code,city,district,is_head_office,is_active',

            'creator:id,name,email',

            'updater:id,name,email',
        ]);

        return response()->json([
            'success' => true,
            'message' =>
                'Supplier retrieved successfully.',
            'data' => [
                'supplier' => (
                    new SupplierResource($supplier)
                )->resolve($request),
            ],
        ]);
    }

    public function update(
        UpdateSupplierRequest $request,
        Supplier $supplier
    ): JsonResponse {
        $validated = $request->validated();

        /** @var User $authenticatedUser */
        $authenticatedUser = $request->user();

        DB::transaction(
            function () use (
                $supplier,
                $validated,
                $authenticatedUser
            ): void {
                $supplier->update([
                    ...$validated,

                    'updated_by' =>
                        $authenticatedUser->id,
                ]);
            }
        );

        $supplier->refresh()->load([
            'company:id,name,code',

            'branch:id,company_id,name,code,city,district,is_head_office,is_active',

            'creator:id,name,email',

            'updater:id,name,email',
        ]);

        return response()->json([
            'success' => true,
            'message' =>
                'Supplier updated successfully.',
            'data' => [
                'supplier' => (
                    new SupplierResource($supplier)
                )->resolve($request),
            ],
        ]);
    }

    public function destroy(
        Request $request,
        Supplier $supplier
    ): JsonResponse {
        Gate::authorize(
            'delete',
            $supplier
        );

        $supplier->delete();

        return response()->json([
            'success' => true,
            'message' =>
                'Supplier deleted successfully.',
            'data' => null,
        ]);
    }

    public function restore(
        Request $request,
        int $supplier
    ): JsonResponse {
        $supplierModel = Supplier::withTrashed()
            ->findOrFail($supplier);

        Gate::authorize(
            'restore',
            $supplierModel
        );

        if (! $supplierModel->trashed()) {
            return response()->json([
                'success' => false,
                'message' =>
                    'The supplier is not deleted.',
                'data' => null,
            ], 422);
        }

        $supplierModel->restore();

        /** @var User $authenticatedUser */
        $authenticatedUser = $request->user();

        $supplierModel->update([
            'updated_by' =>
                $authenticatedUser->id,
        ]);

        $supplierModel->load([
            'company:id,name,code',

            'branch:id,company_id,name,code,city,district,is_head_office,is_active',

            'creator:id,name,email',

            'updater:id,name,email',
        ]);

        return response()->json([
            'success' => true,
            'message' =>
                'Supplier restored successfully.',
            'data' => [
                'supplier' => (
                    new SupplierResource(
                        $supplierModel
                    )
                )->resolve($request),
            ],
        ]);
    }
}