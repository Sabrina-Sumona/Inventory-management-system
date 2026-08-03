<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreCustomerRequest;
use App\Http\Requests\Customer\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class CustomerController extends Controller
{
    public function index(
        Request $request
    ): JsonResponse {
        Gate::authorize(
            'viewAny',
            Customer::class
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

            'customer_type' => [
                'nullable',
                'in:retail,wholesale,corporate,dealer,government,other',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],

            'opening_balance_type' => [
                'nullable',
                'in:receivable,payable',
            ],

            'sort_by' => [
                'nullable',
                'in:name,code,customer_type,created_at,updated_at',
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

        $search =
            $validated['search'] ?? null;

        $branchId =
            $validated['branch_id'] ?? null;

        $customerType =
            $validated['customer_type'] ?? null;

        $isActive =
            $validated['is_active'] ?? null;

        $openingBalanceType =
            $validated['opening_balance_type']
                ?? null;

        $sortBy =
            $validated['sort_by'] ?? 'name';

        $sortDirection =
            $validated['sort_direction'] ?? 'asc';

        $perPage =
            $validated['per_page'] ?? 15;

        $customers = Customer::query()
            ->accessibleTo(
                $authenticatedUser
            )
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
                        function (
                            $searchQuery
                        ) use (
                            $normalizedSearch
                        ): void {
                            $searchQuery
                                ->whereRaw(
                                    'LOWER(customers.name) LIKE ?',
                                    [$normalizedSearch]
                                )
                                ->orWhereRaw(
                                    'LOWER(customers.code) LIKE ?',
                                    [$normalizedSearch]
                                )
                                ->orWhereRaw(
                                    'LOWER(COALESCE(customers.business_name, \'\')) LIKE ?',
                                    [$normalizedSearch]
                                )
                                ->orWhereRaw(
                                    'LOWER(COALESCE(customers.email, \'\')) LIKE ?',
                                    [$normalizedSearch]
                                )
                                ->orWhereRaw(
                                    'LOWER(COALESCE(customers.phone, \'\')) LIKE ?',
                                    [$normalizedSearch]
                                );
                        }
                    );
                }
            )
            ->when(
                $branchId !== null,
                fn ($query) =>
                    $query->where(
                        'customers.branch_id',
                        $branchId
                    )
            )
            ->when(
                $customerType !== null,
                fn ($query) =>
                    $query->where(
                        'customers.customer_type',
                        $customerType
                    )
            )
            ->when(
                $isActive !== null,
                fn ($query) =>
                    $query->where(
                        'customers.is_active',
                        $isActive
                    )
            )
            ->when(
                $openingBalanceType !== null,
                fn ($query) =>
                    $query->where(
                        'customers.opening_balance_type',
                        $openingBalanceType
                    )
            )
            ->orderBy(
                "customers.{$sortBy}",
                $sortDirection
            )
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'success' => true,

            'message' =>
                'Customers retrieved successfully.',

            'data' => [
                'customers' =>
                    CustomerResource::collection(
                        $customers->getCollection()
                    )->resolve($request),

                'pagination' => [
                    'current_page' =>
                        $customers->currentPage(),

                    'last_page' =>
                        $customers->lastPage(),

                    'per_page' =>
                        $customers->perPage(),

                    'total' =>
                        $customers->total(),

                    'from' =>
                        $customers->firstItem(),

                    'to' =>
                        $customers->lastItem(),
                ],
            ],
        ]);
    }

    public function store(
        StoreCustomerRequest $request
    ): JsonResponse {
        $validated =
            $request->validated();

        /** @var User $authenticatedUser */
        $authenticatedUser =
            $request->user();

        $customer = DB::transaction(
            function () use (
                $validated,
                $authenticatedUser
            ): Customer {
                return Customer::query()->create([
                    ...$validated,

                    'billing_country' =>
                        $validated[
                            'billing_country'
                        ] ?? 'Bangladesh',

                    'shipping_country' =>
                        $validated[
                            'shipping_country'
                        ] ?? 'Bangladesh',

                    'payment_term_days' =>
                        $validated[
                            'payment_term_days'
                        ] ?? 0,

                    'credit_limit' =>
                        $validated[
                            'credit_limit'
                        ] ?? 0,

                    'opening_balance' =>
                        $validated[
                            'opening_balance'
                        ] ?? 0,

                    'opening_balance_type' =>
                        $validated[
                            'opening_balance_type'
                        ] ?? 'receivable',

                    'is_active' =>
                        $validated[
                            'is_active'
                        ] ?? true,

                    'created_by' =>
                        $authenticatedUser->id,

                    'updated_by' =>
                        $authenticatedUser->id,
                ]);
            }
        );

        $customer->load([
            'company:id,name,code',

            'branch:id,company_id,name,code,city,district,is_head_office,is_active',

            'creator:id,name,email',

            'updater:id,name,email',
        ]);

        return response()->json([
            'success' => true,

            'message' =>
                'Customer created successfully.',

            'data' => [
                'customer' => (
                    new CustomerResource(
                        $customer
                    )
                )->resolve($request),
            ],
        ], 201);
    }

    public function show(
        Request $request,
        Customer $customer
    ): JsonResponse {
        Gate::authorize(
            'view',
            $customer
        );

        $customer->load([
            'company:id,name,code',

            'branch:id,company_id,name,code,city,district,is_head_office,is_active',

            'creator:id,name,email',

            'updater:id,name,email',
        ]);

        return response()->json([
            'success' => true,

            'message' =>
                'Customer retrieved successfully.',

            'data' => [
                'customer' => (
                    new CustomerResource(
                        $customer
                    )
                )->resolve($request),
            ],
        ]);
    }

    public function update(
        UpdateCustomerRequest $request,
        Customer $customer
    ): JsonResponse {
        $validated =
            $request->validated();

        /** @var User $authenticatedUser */
        $authenticatedUser =
            $request->user();

        DB::transaction(
            function () use (
                $customer,
                $validated,
                $authenticatedUser
            ): void {
                $customer->update([
                    ...$validated,

                    'updated_by' =>
                        $authenticatedUser->id,
                ]);
            }
        );

        $customer
            ->refresh()
            ->load([
                'company:id,name,code',

                'branch:id,company_id,name,code,city,district,is_head_office,is_active',

                'creator:id,name,email',

                'updater:id,name,email',
            ]);

        return response()->json([
            'success' => true,

            'message' =>
                'Customer updated successfully.',

            'data' => [
                'customer' => (
                    new CustomerResource(
                        $customer
                    )
                )->resolve($request),
            ],
        ]);
    }

    public function destroy(
        Request $request,
        Customer $customer
    ): JsonResponse {
        Gate::authorize(
            'delete',
            $customer
        );

        $customer->delete();

        return response()->json([
            'success' => true,

            'message' =>
                'Customer deleted successfully.',

            'data' => null,
        ]);
    }

    public function restore(
        Request $request,
        int $customer
    ): JsonResponse {
        $customerModel =
            Customer::withTrashed()
                ->findOrFail($customer);

        Gate::authorize(
            'restore',
            $customerModel
        );

        if (! $customerModel->trashed()) {
            return response()->json([
                'success' => false,

                'message' =>
                    'The customer is not deleted.',

                'data' => null,
            ], 422);
        }

        $customerModel->restore();

        /** @var User $authenticatedUser */
        $authenticatedUser =
            $request->user();

        $customerModel->update([
            'updated_by' =>
                $authenticatedUser->id,
        ]);

        $customerModel->load([
            'company:id,name,code',

            'branch:id,company_id,name,code,city,district,is_head_office,is_active',

            'creator:id,name,email',

            'updater:id,name,email',
        ]);

        return response()->json([
            'success' => true,

            'message' =>
                'Customer restored successfully.',

            'data' => [
                'customer' => (
                    new CustomerResource(
                        $customerModel
                    )
                )->resolve($request),
            ],
        ]);
    }
}