<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerFinancialSetting\StoreCustomerFinancialSettingRequest;
use App\Http\Requests\CustomerFinancialSetting\UpdateCustomerFinancialSettingRequest;
use App\Http\Resources\CustomerFinancialSettingResource;
use App\Models\CustomerFinancialSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerFinancialSettingController extends Controller
{
    public function index(
        Request $request
    ): JsonResponse {
        $this->authorize(
            'viewAny',
            CustomerFinancialSetting::class
        );

        $validated = $request->validate([
            'customer_id' => [
                'nullable',
                'integer',
                'exists:customers,id',
            ],
            'currency_code' => [
                'nullable',
                'string',
                'size:3',
            ],
            'default_payment_method' => [
                'nullable',
                'string',
                'max:30',
            ],
            'allow_credit_sale' => [
                'nullable',
                'boolean',
            ],
            'is_tax_applicable' => [
                'nullable',
                'boolean',
            ],
            'is_active' => [
                'nullable',
                'boolean',
            ],
            'search' => [
                'nullable',
                'string',
                'max:255',
            ],
            'sort_by' => [
                'nullable',
                'in:id,customer_id,currency_code,payment_term_days,credit_limit,created_at,updated_at',
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
        ]);

        $user = $request->user();

        $query = CustomerFinancialSetting::query()
            ->with([
                'customer.company',
                'customer.branch',
                'creator',
                'updater',
            ])
            ->accessibleTo($user);

        if (
            isset(
                $validated['customer_id']
            )
        ) {
            $query->where(
                'customer_id',
                $validated['customer_id']
            );
        }

        if (
            isset(
                $validated['currency_code']
            )
        ) {
            $query->where(
                'currency_code',
                strtoupper(
                    $validated['currency_code']
                )
            );
        }

        if (
            isset(
                $validated['default_payment_method']
            )
        ) {
            $query->where(
                'default_payment_method',
                $validated['default_payment_method']
            );
        }

        if (
            array_key_exists(
                'allow_credit_sale',
                $validated
            )
        ) {
            $query->where(
                'allow_credit_sale',
                $validated['allow_credit_sale']
            );
        }

        if (
            array_key_exists(
                'is_tax_applicable',
                $validated
            )
        ) {
            $query->where(
                'is_tax_applicable',
                $validated['is_tax_applicable']
            );
        }

        if (
            array_key_exists(
                'is_active',
                $validated
            )
        ) {
            $query->where(
                'is_active',
                $validated['is_active']
            );
        }

        if (
            ! empty(
                $validated['search']
            )
        ) {
            $search =
                $validated['search'];

            $query->where(
                function ($searchQuery) use (
                    $search
                ): void {
                    $searchQuery
                        ->where(
                            'currency_code',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'default_payment_method',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'default_sales_order_term',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'payment_instruction',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'notes',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhereHas(
                            'customer',
                            function (
                                $customerQuery
                            ) use (
                                $search
                            ): void {
                                $customerQuery
                                    ->where(
                                        'name',
                                        'like',
                                        "%{$search}%"
                                    )
                                    ->orWhere(
                                        'code',
                                        'like',
                                        "%{$search}%"
                                    )
                                    ->orWhere(
                                        'business_name',
                                        'like',
                                        "%{$search}%"
                                    );
                            }
                        );
                }
            );
        }

        $sortBy =
            $validated['sort_by']
            ?? 'created_at';

        $sortDirection =
            $validated['sort_direction']
            ?? 'desc';

        $perPage =
            $validated['per_page']
            ?? 15;

        $financialSettings = $query
            ->orderBy(
                $sortBy,
                $sortDirection
            )
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'success' => true,
            'message' =>
                'Customer financial settings retrieved successfully.',
            'data' => [
                'customer_financial_settings' =>
                    CustomerFinancialSettingResource::collection(
                        $financialSettings->items()
                    ),
                'pagination' => [
                    'current_page' =>
                        $financialSettings->currentPage(),
                    'last_page' =>
                        $financialSettings->lastPage(),
                    'per_page' =>
                        $financialSettings->perPage(),
                    'total' =>
                        $financialSettings->total(),
                    'from' =>
                        $financialSettings->firstItem(),
                    'to' =>
                        $financialSettings->lastItem(),
                ],
            ],
        ]);
    }

    public function store(
        StoreCustomerFinancialSettingRequest $request
    ): JsonResponse {
        $this->authorize(
            'create',
            CustomerFinancialSetting::class
        );

        $validated =
            $request->validated();

        $financialSetting =
            DB::transaction(
                function () use (
                    $validated,
                    $request
                ): CustomerFinancialSetting {
                    $validated['currency_code'] =
                        strtoupper(
                            $validated['currency_code']
                            ?? 'BDT'
                        );

                    $validated['created_by'] =
                        $request->user()->id;

                    $validated['updated_by'] =
                        $request->user()->id;

                    return CustomerFinancialSetting::query()
                        ->create(
                            $validated
                        );
                }
            );

        $financialSetting->load([
            'customer.company',
            'customer.branch',
            'creator',
            'updater',
        ]);

        return response()->json(
            [
                'success' => true,
                'message' =>
                    'Customer financial setting created successfully.',
                'data' => [
                    'customer_financial_setting' =>
                        new CustomerFinancialSettingResource(
                            $financialSetting
                        ),
                ],
            ],
            201
        );
    }

    public function show(
        CustomerFinancialSetting $customerFinancialSetting
    ): JsonResponse {
        $this->authorize(
            'view',
            $customerFinancialSetting
        );

        $customerFinancialSetting->load([
            'customer.company',
            'customer.branch',
            'creator',
            'updater',
        ]);

        return response()->json([
            'success' => true,
            'message' =>
                'Customer financial setting retrieved successfully.',
            'data' => [
                'customer_financial_setting' =>
                    new CustomerFinancialSettingResource(
                        $customerFinancialSetting
                    ),
            ],
        ]);
    }

    public function update(
        UpdateCustomerFinancialSettingRequest $request,
        CustomerFinancialSetting $customerFinancialSetting
    ): JsonResponse {
        $this->authorize(
            'update',
            $customerFinancialSetting
        );

        $validated =
            $request->validated();

        DB::transaction(
            function () use (
                $validated,
                $request,
                $customerFinancialSetting
            ): void {
                if (
                    isset(
                        $validated['currency_code']
                    )
                ) {
                    $validated['currency_code'] =
                        strtoupper(
                            $validated['currency_code']
                        );
                }

                $validated['updated_by'] =
                    $request->user()->id;

                $customerFinancialSetting->update(
                    $validated
                );
            }
        );

        $customerFinancialSetting
            ->refresh()
            ->load([
                'customer.company',
                'customer.branch',
                'creator',
                'updater',
            ]);

        return response()->json([
            'success' => true,
            'message' =>
                'Customer financial setting updated successfully.',
            'data' => [
                'customer_financial_setting' =>
                    new CustomerFinancialSettingResource(
                        $customerFinancialSetting
                    ),
            ],
        ]);
    }

    public function destroy(
        CustomerFinancialSetting $customerFinancialSetting
    ): JsonResponse {
        $this->authorize(
            'delete',
            $customerFinancialSetting
        );

        $customerFinancialSetting->delete();

        return response()->json([
            'success' => true,
            'message' =>
                'Customer financial setting deleted successfully.',
        ]);
    }
}