<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SupplierFinancialSetting\StoreSupplierFinancialSettingRequest;
use App\Http\Requests\SupplierFinancialSetting\UpdateSupplierFinancialSettingRequest;
use App\Http\Resources\SupplierFinancialSettingResource;
use App\Models\SupplierFinancialSetting;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class SupplierFinancialSettingController extends Controller
{
    public function index(
        Request $request
    ): JsonResponse {
        Gate::authorize(
            'viewAny',
            SupplierFinancialSetting::class
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

            'currency_code' => [
                'nullable',
                'string',
                'size:3',
            ],

            'default_payment_method' => [
                'nullable',
                'in:cash,bank_transfer,cheque,mobile_banking,credit',
            ],

            'allow_credit_purchase' => [
                'nullable',
                'boolean',
            ],

            'is_tax_applicable' => [
                'nullable',
                'boolean',
            ],

            'is_withholding_tax_applicable' => [
                'nullable',
                'boolean',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],

            'sort_by' => [
                'nullable',
                'in:currency_code,credit_limit,payment_term_days,created_at,updated_at',
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

        $currencyCode =
            $validated['currency_code'] ?? null;

        $defaultPaymentMethod =
            $validated['default_payment_method']
            ?? null;

        $allowCreditPurchase =
            $validated['allow_credit_purchase']
            ?? null;

        $isTaxApplicable =
            $validated['is_tax_applicable']
            ?? null;

        $isWithholdingTaxApplicable =
            $validated[
                'is_withholding_tax_applicable'
            ] ?? null;

        $isActive =
            $validated['is_active'] ?? null;

        $sortBy =
            $validated['sort_by']
            ?? 'created_at';

        $sortDirection =
            $validated['sort_direction']
            ?? 'desc';

        $perPage =
            $validated['per_page'] ?? 15;

        $settings =
            SupplierFinancialSetting::query()
                ->accessibleTo(
                    $authenticatedUser
                )
                ->with([
                    'supplier:id,company_id,branch_id,name,code,business_name,is_active',
                    'creator:id,name,email',
                    'updater:id,name,email',
                ])
                ->when(
                    $supplierId !== null,
                    fn ($query) =>
                        $query->where(
                            'supplier_financial_settings.supplier_id',
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

                        $query->whereHas(
                            'supplier',
                            function (
                                $supplierQuery
                            ) use (
                                $normalizedSearch
                            ): void {
                                $supplierQuery
                                    ->whereRaw(
                                        'LOWER(suppliers.name) LIKE ?',
                                        [
                                            $normalizedSearch,
                                        ]
                                    )
                                    ->orWhereRaw(
                                        'LOWER(suppliers.code) LIKE ?',
                                        [
                                            $normalizedSearch,
                                        ]
                                    )
                                    ->orWhereRaw(
                                        'LOWER(COALESCE(suppliers.business_name, \'\')) LIKE ?',
                                        [
                                            $normalizedSearch,
                                        ]
                                    );
                            }
                        );
                    }
                )
                ->when(
                    $currencyCode !== null,
                    fn ($query) =>
                        $query->where(
                            'supplier_financial_settings.currency_code',
                            strtoupper(
                                $currencyCode
                            )
                        )
                )
                ->when(
                    $defaultPaymentMethod !==
                        null,
                    fn ($query) =>
                        $query->where(
                            'supplier_financial_settings.default_payment_method',
                            $defaultPaymentMethod
                        )
                )
                ->when(
                    $allowCreditPurchase !==
                        null,
                    fn ($query) =>
                        $query->where(
                            'supplier_financial_settings.allow_credit_purchase',
                            $allowCreditPurchase
                        )
                )
                ->when(
                    $isTaxApplicable !== null,
                    fn ($query) =>
                        $query->where(
                            'supplier_financial_settings.is_tax_applicable',
                            $isTaxApplicable
                        )
                )
                ->when(
                    $isWithholdingTaxApplicable !==
                        null,
                    fn ($query) =>
                        $query->where(
                            'supplier_financial_settings.is_withholding_tax_applicable',
                            $isWithholdingTaxApplicable
                        )
                )
                ->when(
                    $isActive !== null,
                    fn ($query) =>
                        $query->where(
                            'supplier_financial_settings.is_active',
                            $isActive
                        )
                )
                ->orderBy(
                    "supplier_financial_settings.{$sortBy}",
                    $sortDirection
                )
                ->paginate($perPage)
                ->withQueryString();

        return response()->json([
            'success' => true,
            'message' =>
                'Supplier financial settings retrieved successfully.',
            'data' => [
                'supplier_financial_settings' =>
                    SupplierFinancialSettingResource::collection(
                        $settings->getCollection()
                    )->resolve($request),

                'pagination' => [
                    'current_page' =>
                        $settings->currentPage(),

                    'last_page' =>
                        $settings->lastPage(),

                    'per_page' =>
                        $settings->perPage(),

                    'total' =>
                        $settings->total(),

                    'from' =>
                        $settings->firstItem(),

                    'to' =>
                        $settings->lastItem(),
                ],
            ],
        ]);
    }

    public function store(
        StoreSupplierFinancialSettingRequest $request
    ): JsonResponse {
        $validated = $request->validated();

        /** @var User $authenticatedUser */
        $authenticatedUser = $request->user();

        $setting = DB::transaction(
            function () use (
                $validated,
                $authenticatedUser
            ): SupplierFinancialSetting {
                $currencyCode = strtoupper(
                    $validated[
                        'currency_code'
                    ] ?? 'BDT'
                );

                return SupplierFinancialSetting::create([
                    ...$validated,

                    'currency_code' =>
                        $currencyCode,

                    'default_payment_method' =>
                        $validated[
                            'default_payment_method'
                        ] ?? 'bank_transfer',

                    'payment_term_days' =>
                        $validated[
                            'payment_term_days'
                        ] ?? 0,

                    'credit_limit' =>
                        $validated[
                            'credit_limit'
                        ] ?? 0,

                    'allow_credit_purchase' =>
                        $validated[
                            'allow_credit_purchase'
                        ] ?? false,

                    'block_purchase_on_credit_limit' =>
                        $validated[
                            'block_purchase_on_credit_limit'
                        ] ?? true,

                    'default_purchase_discount_percent' =>
                        $validated[
                            'default_purchase_discount_percent'
                        ] ?? 0,

                    'is_tax_applicable' =>
                        $validated[
                            'is_tax_applicable'
                        ] ?? false,

                    'default_tax_percent' =>
                        $validated[
                            'default_tax_percent'
                        ] ?? 0,

                    'is_withholding_tax_applicable' =>
                        $validated[
                            'is_withholding_tax_applicable'
                        ] ?? false,

                    'withholding_tax_percent' =>
                        $validated[
                            'withholding_tax_percent'
                        ] ?? 0,

                    'purchase_price_basis' =>
                        $validated[
                            'purchase_price_basis'
                        ] ?? 'exclusive_of_tax',

                    'default_purchase_order_term' =>
                        $validated[
                            'default_purchase_order_term'
                        ] ?? 'standard',

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

        $setting->load([
            'supplier:id,company_id,branch_id,name,code,business_name,is_active',
            'creator:id,name,email',
            'updater:id,name,email',
        ]);

        return response()->json([
            'success' => true,
            'message' =>
                'Supplier financial setting created successfully.',
            'data' => [
                'supplier_financial_setting' => (
                    new SupplierFinancialSettingResource(
                        $setting
                    )
                )->resolve($request),
            ],
        ], 201);
    }

    public function show(
        Request $request,
        SupplierFinancialSetting $supplierFinancialSetting
    ): JsonResponse {
        Gate::authorize(
            'view',
            $supplierFinancialSetting
        );

        $supplierFinancialSetting->load([
            'supplier:id,company_id,branch_id,name,code,business_name,is_active',
            'creator:id,name,email',
            'updater:id,name,email',
        ]);

        return response()->json([
            'success' => true,
            'message' =>
                'Supplier financial setting retrieved successfully.',
            'data' => [
                'supplier_financial_setting' => (
                    new SupplierFinancialSettingResource(
                        $supplierFinancialSetting
                    )
                )->resolve($request),
            ],
        ]);
    }

    public function update(
        UpdateSupplierFinancialSettingRequest $request,
        SupplierFinancialSetting $supplierFinancialSetting
    ): JsonResponse {
        $validated = $request->validated();

        /** @var User $authenticatedUser */
        $authenticatedUser = $request->user();

        $payload = [
            ...$validated,

            'updated_by' =>
                $authenticatedUser->id,
        ];

        if (
            array_key_exists(
                'currency_code',
                $payload
            )
        ) {
            $payload['currency_code'] =
                strtoupper(
                    $payload[
                        'currency_code'
                    ]
                );
        }

        DB::transaction(
            function () use (
                $supplierFinancialSetting,
                $payload
            ): void {
                $supplierFinancialSetting
                    ->update($payload);
            }
        );

        $supplierFinancialSetting
            ->refresh()
            ->load([
                'supplier:id,company_id,branch_id,name,code,business_name,is_active',
                'creator:id,name,email',
                'updater:id,name,email',
            ]);

        return response()->json([
            'success' => true,
            'message' =>
                'Supplier financial setting updated successfully.',
            'data' => [
                'supplier_financial_setting' => (
                    new SupplierFinancialSettingResource(
                        $supplierFinancialSetting
                    )
                )->resolve($request),
            ],
        ]);
    }

    public function destroy(
        Request $request,
        SupplierFinancialSetting $supplierFinancialSetting
    ): JsonResponse {
        Gate::authorize(
            'delete',
            $supplierFinancialSetting
        );

        $supplierFinancialSetting->delete();

        return response()->json([
            'success' => true,
            'message' =>
                'Supplier financial setting deleted successfully.',
            'data' => null,
        ]);
    }
}