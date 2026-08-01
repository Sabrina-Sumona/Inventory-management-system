<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SupplierContact\StoreSupplierContactRequest;
use App\Http\Requests\SupplierContact\UpdateSupplierContactRequest;
use App\Http\Resources\SupplierContactResource;
use App\Models\SupplierContact;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class SupplierContactController extends Controller
{
    public function index(
        Request $request
    ): JsonResponse {
        Gate::authorize(
            'viewAny',
            SupplierContact::class
        );

        /** @var User $authenticatedUser */
        $authenticatedUser = $request->user();

        $validated = $request->validate([
            'supplier_id' => [
                'nullable',
                'integer',
                'exists:suppliers,id',
            ],

            'search' => [
                'nullable',
                'string',
                'max:100',
            ],

            'contact_type' => [
                'nullable',
                'in:general,sales,accounts,support,management',
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

        $supplierId =
            $validated['supplier_id'] ?? null;

        $search =
            $validated['search'] ?? null;

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

        $contacts = SupplierContact::query()
            ->accessibleTo($authenticatedUser)
            ->with([
                'supplier:id,company_id,branch_id,name,code,business_name,is_active',
                'creator:id,name,email',
                'updater:id,name,email',
            ])
            ->when(
                $supplierId !== null,
                fn ($query) => $query->where(
                    'supplier_contacts.supplier_id',
                    $supplierId
                )
            )
            ->when(
                $search,
                function (
                    $query,
                    string $search
                ): void {
                    $normalizedSearch =
                        '%' .
                        Str::lower($search) .
                        '%';

                    $query->where(
                        function ($searchQuery) use (
                            $normalizedSearch
                        ): void {
                            $searchQuery
                                ->whereRaw(
                                    'LOWER(supplier_contacts.name) LIKE ?',
                                    [$normalizedSearch]
                                )
                                ->orWhereRaw(
                                    'LOWER(COALESCE(supplier_contacts.designation, \'\')) LIKE ?',
                                    [$normalizedSearch]
                                )
                                ->orWhereRaw(
                                    'LOWER(COALESCE(supplier_contacts.department, \'\')) LIKE ?',
                                    [$normalizedSearch]
                                )
                                ->orWhereRaw(
                                    'LOWER(COALESCE(supplier_contacts.email, \'\')) LIKE ?',
                                    [$normalizedSearch]
                                )
                                ->orWhereRaw(
                                    'LOWER(COALESCE(supplier_contacts.phone, \'\')) LIKE ?',
                                    [$normalizedSearch]
                                )
                                ->orWhereRaw(
                                    'LOWER(COALESCE(supplier_contacts.alternate_phone, \'\')) LIKE ?',
                                    [$normalizedSearch]
                                );
                        }
                    );
                }
            )
            ->when(
                $contactType !== null,
                fn ($query) => $query->where(
                    'supplier_contacts.contact_type',
                    $contactType
                )
            )
            ->when(
                $isPrimary !== null,
                fn ($query) => $query->where(
                    'supplier_contacts.is_primary',
                    $isPrimary
                )
            )
            ->when(
                $isActive !== null,
                fn ($query) => $query->where(
                    'supplier_contacts.is_active',
                    $isActive
                )
            )
            ->orderBy(
                "supplier_contacts.{$sortBy}",
                $sortDirection
            )
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'success' => true,
            'message' =>
                'Supplier contacts retrieved successfully.',
            'data' => [
                'supplier_contacts' =>
                    SupplierContactResource::collection(
                        $contacts->getCollection()
                    )->resolve($request),

                'pagination' => [
                    'current_page' =>
                        $contacts->currentPage(),

                    'last_page' =>
                        $contacts->lastPage(),

                    'per_page' =>
                        $contacts->perPage(),

                    'total' =>
                        $contacts->total(),

                    'from' =>
                        $contacts->firstItem(),

                    'to' =>
                        $contacts->lastItem(),
                ],
            ],
        ]);
    }

    public function store(
        StoreSupplierContactRequest $request
    ): JsonResponse {
        $validated = $request->validated();

        /** @var User $authenticatedUser */
        $authenticatedUser = $request->user();

        $supplierContact = DB::transaction(
            function () use (
                $validated,
                $authenticatedUser
            ): SupplierContact {
                $isPrimary =
                    $validated['is_primary']
                        ?? false;

                if ($isPrimary) {
                    SupplierContact::query()
                        ->where(
                            'supplier_id',
                            $validated['supplier_id']
                        )
                        ->where(
                            'is_primary',
                            true
                        )
                        ->update([
                            'is_primary' => false,
                            'updated_by' =>
                                $authenticatedUser->id,
                            'updated_at' => now(),
                        ]);
                }

                return SupplierContact::create([
                    ...$validated,

                    'contact_type' =>
                        $validated['contact_type']
                            ?? 'general',

                    'is_primary' =>
                        $isPrimary,

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

        $supplierContact->load([
            'supplier:id,company_id,branch_id,name,code,business_name,is_active',
            'creator:id,name,email',
            'updater:id,name,email',
        ]);

        return response()->json([
            'success' => true,
            'message' =>
                'Supplier contact created successfully.',
            'data' => [
                'supplier_contact' => (
                    new SupplierContactResource(
                        $supplierContact
                    )
                )->resolve($request),
            ],
        ], 201);
    }

    public function show(
        Request $request,
        SupplierContact $supplierContact
    ): JsonResponse {
        Gate::authorize(
            'view',
            $supplierContact
        );

        $supplierContact->load([
            'supplier:id,company_id,branch_id,name,code,business_name,is_active',
            'creator:id,name,email',
            'updater:id,name,email',
        ]);

        return response()->json([
            'success' => true,
            'message' =>
                'Supplier contact retrieved successfully.',
            'data' => [
                'supplier_contact' => (
                    new SupplierContactResource(
                        $supplierContact
                    )
                )->resolve($request),
            ],
        ]);
    }

    public function update(
        UpdateSupplierContactRequest $request,
        SupplierContact $supplierContact
    ): JsonResponse {
        $validated = $request->validated();

        /** @var User $authenticatedUser */
        $authenticatedUser = $request->user();

        DB::transaction(
            function () use (
                $validated,
                $supplierContact,
                $authenticatedUser
            ): void {
                $willBePrimary =
                    array_key_exists(
                        'is_primary',
                        $validated
                    )
                    && $validated['is_primary'];

                if ($willBePrimary) {
                    SupplierContact::query()
                        ->where(
                            'supplier_id',
                            $supplierContact
                                ->supplier_id
                        )
                        ->whereKeyNot(
                            $supplierContact->id
                        )
                        ->where(
                            'is_primary',
                            true
                        )
                        ->update([
                            'is_primary' => false,
                            'updated_by' =>
                                $authenticatedUser->id,
                            'updated_at' => now(),
                        ]);
                }

                $supplierContact->update([
                    ...$validated,

                    'updated_by' =>
                        $authenticatedUser->id,
                ]);
            }
        );

        $supplierContact
            ->refresh()
            ->load([
                'supplier:id,company_id,branch_id,name,code,business_name,is_active',
                'creator:id,name,email',
                'updater:id,name,email',
            ]);

        return response()->json([
            'success' => true,
            'message' =>
                'Supplier contact updated successfully.',
            'data' => [
                'supplier_contact' => (
                    new SupplierContactResource(
                        $supplierContact
                    )
                )->resolve($request),
            ],
        ]);
    }

    public function destroy(
        Request $request,
        SupplierContact $supplierContact
    ): JsonResponse {
        Gate::authorize(
            'delete',
            $supplierContact
        );

        $supplierContact->delete();

        return response()->json([
            'success' => true,
            'message' =>
                'Supplier contact deleted successfully.',
            'data' => null,
        ]);
    }

    public function restore(
        Request $request,
        int $supplierContact
    ): JsonResponse {
        $supplierContactModel =
            SupplierContact::withTrashed()
                ->findOrFail($supplierContact);

        Gate::authorize(
            'restore',
            $supplierContactModel
        );

        if (! $supplierContactModel->trashed()) {
            return response()->json([
                'success' => false,
                'message' =>
                    'The supplier contact is not deleted.',
                'data' => null,
            ], 422);
        }

        /** @var User $authenticatedUser */
        $authenticatedUser = $request->user();

        DB::transaction(
            function () use (
                $supplierContactModel,
                $authenticatedUser
            ): void {
                if (
                    $supplierContactModel
                        ->is_primary
                ) {
                    SupplierContact::query()
                        ->where(
                            'supplier_id',
                            $supplierContactModel
                                ->supplier_id
                        )
                        ->whereKeyNot(
                            $supplierContactModel->id
                        )
                        ->where(
                            'is_primary',
                            true
                        )
                        ->update([
                            'is_primary' => false,
                            'updated_by' =>
                                $authenticatedUser->id,
                            'updated_at' => now(),
                        ]);
                }

                $supplierContactModel->restore();

                $supplierContactModel->update([
                    'updated_by' =>
                        $authenticatedUser->id,
                ]);
            }
        );

        $supplierContactModel->load([
            'supplier:id,company_id,branch_id,name,code,business_name,is_active',
            'creator:id,name,email',
            'updater:id,name,email',
        ]);

        return response()->json([
            'success' => true,
            'message' =>
                'Supplier contact restored successfully.',
            'data' => [
                'supplier_contact' => (
                    new SupplierContactResource(
                        $supplierContactModel
                    )
                )->resolve($request),
            ],
        ]);
    }
}