<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerContact\StoreCustomerContactRequest;
use App\Http\Requests\CustomerContact\UpdateCustomerContactRequest;
use App\Http\Resources\CustomerContactResource;
use App\Models\CustomerContact;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class CustomerContactController extends Controller
{
    public function index(
        Request $request
    ): JsonResponse {
        Gate::authorize(
            'viewAny',
            CustomerContact::class
        );

        /** @var User $authenticatedUser */
        $authenticatedUser =
            $request->user();

        $validated = $request->validate([
            'search' => [
                'nullable',
                'string',
                'max:100',
            ],

            'customer_id' => [
                'nullable',
                'integer',
                'exists:customers,id',
            ],

            'contact_type' => [
                'nullable',
                'in:general,sales,accounts,management,support,purchase,other',
            ],

            'is_primary' => [
                'nullable',
                'boolean',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],

            'sort_by' => [
                'nullable',
                'in:name,contact_type,created_at,updated_at',
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

        $customerId =
            $validated['customer_id'] ?? null;

        $contactType =
            $validated['contact_type'] ?? null;

        $isPrimary =
            $validated['is_primary'] ?? null;

        $isActive =
            $validated['is_active'] ?? null;

        $sortBy =
            $validated['sort_by'] ?? 'name';

        $sortDirection =
            $validated['sort_direction'] ?? 'asc';

        $perPage =
            $validated['per_page'] ?? 15;

        $customerContacts =
            CustomerContact::query()
                ->accessibleTo(
                    $authenticatedUser
                )
                ->with([
                    'customer:id,company_id,branch_id,name,code,business_name,customer_type,is_active',

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
                            . Str::lower(
                                $search
                            )
                            . '%';

                        $query->where(
                            function (
                                $searchQuery
                            ) use (
                                $normalizedSearch
                            ): void {
                                $searchQuery
                                    ->whereRaw(
                                        'LOWER(customer_contacts.name) LIKE ?',
                                        [
                                            $normalizedSearch,
                                        ]
                                    )
                                    ->orWhereRaw(
                                        'LOWER(COALESCE(customer_contacts.designation, \'\')) LIKE ?',
                                        [
                                            $normalizedSearch,
                                        ]
                                    )
                                    ->orWhereRaw(
                                        'LOWER(COALESCE(customer_contacts.department, \'\')) LIKE ?',
                                        [
                                            $normalizedSearch,
                                        ]
                                    )
                                    ->orWhereRaw(
                                        'LOWER(COALESCE(customer_contacts.email, \'\')) LIKE ?',
                                        [
                                            $normalizedSearch,
                                        ]
                                    )
                                    ->orWhereRaw(
                                        'LOWER(COALESCE(customer_contacts.phone, \'\')) LIKE ?',
                                        [
                                            $normalizedSearch,
                                        ]
                                    );
                            }
                        );
                    }
                )
                ->when(
                    $customerId !== null,
                    fn ($query) =>
                        $query->where(
                            'customer_contacts.customer_id',
                            $customerId
                        )
                )
                ->when(
                    $contactType !== null,
                    fn ($query) =>
                        $query->where(
                            'customer_contacts.contact_type',
                            $contactType
                        )
                )
                ->when(
                    $isPrimary !== null,
                    fn ($query) =>
                        $query->where(
                            'customer_contacts.is_primary',
                            $isPrimary
                        )
                )
                ->when(
                    $isActive !== null,
                    fn ($query) =>
                        $query->where(
                            'customer_contacts.is_active',
                            $isActive
                        )
                )
                ->orderBy(
                    "customer_contacts.{$sortBy}",
                    $sortDirection
                )
                ->paginate($perPage)
                ->withQueryString();

        return response()->json([
            'success' => true,

            'message' =>
                'Customer contacts retrieved successfully.',

            'data' => [
                'customer_contacts' =>
                    CustomerContactResource::collection(
                        $customerContacts
                            ->getCollection()
                    )->resolve($request),

                'pagination' => [
                    'current_page' =>
                        $customerContacts
                            ->currentPage(),

                    'last_page' =>
                        $customerContacts
                            ->lastPage(),

                    'per_page' =>
                        $customerContacts
                            ->perPage(),

                    'total' =>
                        $customerContacts
                            ->total(),

                    'from' =>
                        $customerContacts
                            ->firstItem(),

                    'to' =>
                        $customerContacts
                            ->lastItem(),
                ],
            ],
        ]);
    }

    public function store(
        StoreCustomerContactRequest $request
    ): JsonResponse {
        $validated =
            $request->validated();

        /** @var User $authenticatedUser */
        $authenticatedUser =
            $request->user();

        $customerContact =
            DB::transaction(
                function () use (
                    $validated,
                    $authenticatedUser
                ): CustomerContact {
                    if (
                        $validated['is_primary']
                        ?? false
                    ) {
                        CustomerContact::query()
                            ->where(
                                'customer_id',
                                $validated[
                                    'customer_id'
                                ]
                            )
                            ->where(
                                'is_primary',
                                true
                            )
                            ->update([
                                'is_primary' => false,
                                'updated_by' =>
                                    $authenticatedUser
                                        ->id,
                                'updated_at' =>
                                    now(),
                            ]);
                    }

                    return CustomerContact::query()
                        ->create([
                            ...$validated,

                            'contact_type' =>
                                $validated[
                                    'contact_type'
                                ] ?? 'general',

                            'is_primary' =>
                                $validated[
                                    'is_primary'
                                ] ?? false,

                            'is_active' =>
                                $validated[
                                    'is_active'
                                ] ?? true,

                            'created_by' =>
                                $authenticatedUser
                                    ->id,

                            'updated_by' =>
                                $authenticatedUser
                                    ->id,
                        ]);
                }
            );

        $customerContact->load([
            'customer:id,company_id,branch_id,name,code,business_name,customer_type,is_active',

            'creator:id,name,email',

            'updater:id,name,email',
        ]);

        return response()->json([
            'success' => true,

            'message' =>
                'Customer contact created successfully.',

            'data' => [
                'customer_contact' => (
                    new CustomerContactResource(
                        $customerContact
                    )
                )->resolve($request),
            ],
        ], 201);
    }

    public function show(
        Request $request,
        CustomerContact $customerContact
    ): JsonResponse {
        Gate::authorize(
            'view',
            $customerContact
        );

        $customerContact->load([
            'customer:id,company_id,branch_id,name,code,business_name,customer_type,is_active',

            'creator:id,name,email',

            'updater:id,name,email',
        ]);

        return response()->json([
            'success' => true,

            'message' =>
                'Customer contact retrieved successfully.',

            'data' => [
                'customer_contact' => (
                    new CustomerContactResource(
                        $customerContact
                    )
                )->resolve($request),
            ],
        ]);
    }

    public function update(
        UpdateCustomerContactRequest $request,
        CustomerContact $customerContact
    ): JsonResponse {
        $validated =
            $request->validated();

        /** @var User $authenticatedUser */
        $authenticatedUser =
            $request->user();

        DB::transaction(
            function () use (
                $customerContact,
                $validated,
                $authenticatedUser
            ): void {
                $targetCustomerId =
                    $validated['customer_id']
                    ?? $customerContact
                        ->customer_id;

                if (
                    array_key_exists(
                        'is_primary',
                        $validated
                    )
                    && $validated['is_primary']
                ) {
                    CustomerContact::query()
                        ->where(
                            'customer_id',
                            $targetCustomerId
                        )
                        ->whereKeyNot(
                            $customerContact->id
                        )
                        ->where(
                            'is_primary',
                            true
                        )
                        ->update([
                            'is_primary' => false,
                            'updated_by' =>
                                $authenticatedUser
                                    ->id,
                            'updated_at' =>
                                now(),
                        ]);
                }

                $customerContact->update([
                    ...$validated,

                    'updated_by' =>
                        $authenticatedUser->id,
                ]);
            }
        );

        $customerContact
            ->refresh()
            ->load([
                'customer:id,company_id,branch_id,name,code,business_name,customer_type,is_active',

                'creator:id,name,email',

                'updater:id,name,email',
            ]);

        return response()->json([
            'success' => true,

            'message' =>
                'Customer contact updated successfully.',

            'data' => [
                'customer_contact' => (
                    new CustomerContactResource(
                        $customerContact
                    )
                )->resolve($request),
            ],
        ]);
    }

    public function destroy(
        Request $request,
        CustomerContact $customerContact
    ): JsonResponse {
        Gate::authorize(
            'delete',
            $customerContact
        );

        $customerContact->delete();

        return response()->json([
            'success' => true,

            'message' =>
                'Customer contact deleted successfully.',

            'data' => null,
        ]);
    }

    public function restore(
        Request $request,
        int $customerContact
    ): JsonResponse {
        $customerContactModel =
            CustomerContact::withTrashed()
                ->findOrFail(
                    $customerContact
                );

        Gate::authorize(
            'restore',
            $customerContactModel
        );

        if (
            ! $customerContactModel
                ->trashed()
        ) {
            return response()->json([
                'success' => false,

                'message' =>
                    'The customer contact is not deleted.',

                'data' => null,
            ], 422);
        }

        /** @var User $authenticatedUser */
        $authenticatedUser =
            $request->user();

        DB::transaction(
            function () use (
                $customerContactModel,
                $authenticatedUser
            ): void {
                if (
                    $customerContactModel
                        ->is_primary
                ) {
                    CustomerContact::query()
                        ->where(
                            'customer_id',
                            $customerContactModel
                                ->customer_id
                        )
                        ->where(
                            'is_primary',
                            true
                        )
                        ->update([
                            'is_primary' => false,
                            'updated_by' =>
                                $authenticatedUser
                                    ->id,
                            'updated_at' =>
                                now(),
                        ]);
                }

                $customerContactModel
                    ->restore();

                $customerContactModel
                    ->update([
                        'updated_by' =>
                            $authenticatedUser
                                ->id,
                    ]);
            }
        );

        $customerContactModel->load([
            'customer:id,company_id,branch_id,name,code,business_name,customer_type,is_active',

            'creator:id,name,email',

            'updater:id,name,email',
        ]);

        return response()->json([
            'success' => true,

            'message' =>
                'Customer contact restored successfully.',

            'data' => [
                'customer_contact' => (
                    new CustomerContactResource(
                        $customerContactModel
                    )
                )->resolve($request),
            ],
        ]);
    }
}