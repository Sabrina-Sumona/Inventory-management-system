<?php

namespace App\Http\Requests\CustomerFinancialSetting;

use App\Models\Customer;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreCustomerFinancialSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user =
            $this->user();

        return [
            'customer_id' => [
                'required',
                'integer',
                Rule::exists(
                    'customers',
                    'id'
                )->where(
                    function ($query) use (
                        $user
                    ): void {
                        if (
                            $user === null
                            || (
                                ! $user->isSuperAdmin()
                                && $user->company_id === null
                            )
                        ) {
                            $query->whereRaw(
                                '1 = 0'
                            );

                            return;
                        }

                        if (
                            ! $user->isSuperAdmin()
                        ) {
                            $branchIds = $user
                                ->branches()
                                ->pluck(
                                    'branches.id'
                                )
                                ->all();

                            $query
                                ->where(
                                    'company_id',
                                    $user->company_id
                                )
                                ->whereNull(
                                    'deleted_at'
                                )
                                ->where(
                                    function (
                                        $branchQuery
                                    ) use (
                                        $branchIds
                                    ): void {
                                        $branchQuery
                                            ->whereNull(
                                                'branch_id'
                                            )
                                            ->orWhereIn(
                                                'branch_id',
                                                $branchIds
                                            );
                                    }
                                );
                        } else {
                            $query->whereNull(
                                'deleted_at'
                            );
                        }
                    }
                ),
                Rule::unique(
                    'customer_financial_settings',
                    'customer_id'
                ),
            ],
            'currency_code' => [
                'sometimes',
                'string',
                'size:3',
                'regex:/^[A-Za-z]{3}$/',
            ],
            'default_payment_method' => [
                'sometimes',
                'string',
                Rule::in([
                    'cash',
                    'bank_transfer',
                    'mobile_banking',
                    'cheque',
                    'card',
                    'credit',
                ]),
            ],
            'payment_term_days' => [
                'sometimes',
                'integer',
                'min:0',
                'max:3650',
            ],
            'credit_limit' => [
                'sometimes',
                'numeric',
                'min:0',
                'max:9999999999999999.99',
            ],
            'allow_credit_sale' => [
                'sometimes',
                'boolean',
            ],
            'block_sale_on_credit_limit' => [
                'sometimes',
                'boolean',
            ],
            'default_sales_discount_percent' => [
                'sometimes',
                'numeric',
                'min:0',
                'max:100',
            ],
            'is_tax_applicable' => [
                'sometimes',
                'boolean',
            ],
            'default_tax_percent' => [
                'sometimes',
                'numeric',
                'min:0',
                'max:100',
            ],
            'is_withholding_tax_applicable' => [
                'sometimes',
                'boolean',
            ],
            'withholding_tax_percent' => [
                'sometimes',
                'numeric',
                'min:0',
                'max:100',
            ],
            'sales_price_basis' => [
                'sometimes',
                'string',
                Rule::in([
                    'exclusive_of_tax',
                    'inclusive_of_tax',
                ]),
            ],
            'default_sales_order_term' => [
                'sometimes',
                'string',
                Rule::in([
                    'standard',
                    'advance_payment',
                    'cash_on_delivery',
                    'partial_advance',
                    'credit',
                ]),
            ],
            'payment_instruction' => [
                'nullable',
                'string',
                'max:5000',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:5000',
            ],
            'is_active' => [
                'sometimes',
                'boolean',
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (
                Validator $validator
            ): void {
                $allowCreditSale =
                    $this->boolean(
                        'allow_credit_sale'
                    );

                $creditLimit =
                    (float) $this->input(
                        'credit_limit',
                        0
                    );

                if (
                    $allowCreditSale
                    && $creditLimit <= 0
                ) {
                    $validator
                        ->errors()
                        ->add(
                            'credit_limit',
                            'The credit limit must be greater than zero when credit sales are allowed.'
                        );
                }

                $isTaxApplicable =
                    $this->boolean(
                        'is_tax_applicable'
                    );

                $taxPercent =
                    (float) $this->input(
                        'default_tax_percent',
                        0
                    );

                if (
                    ! $isTaxApplicable
                    && $taxPercent > 0
                ) {
                    $validator
                        ->errors()
                        ->add(
                            'default_tax_percent',
                            'The default tax percentage must be zero when tax is not applicable.'
                        );
                }

                $isWithholdingTaxApplicable =
                    $this->boolean(
                        'is_withholding_tax_applicable'
                    );

                $withholdingTaxPercent =
                    (float) $this->input(
                        'withholding_tax_percent',
                        0
                    );

                if (
                    ! $isWithholdingTaxApplicable
                    && $withholdingTaxPercent > 0
                ) {
                    $validator
                        ->errors()
                        ->add(
                            'withholding_tax_percent',
                            'The withholding tax percentage must be zero when withholding tax is not applicable.'
                        );
                }

                $customer =
                    Customer::query()
                        ->find(
                            $this->input(
                                'customer_id'
                            )
                        );

                if (
                    $customer !== null
                    && ! $allowCreditSale
                    && $creditLimit > 0
                ) {
                    $validator
                        ->errors()
                        ->add(
                            'credit_limit',
                            'The credit limit must be zero when credit sales are not allowed.'
                        );
                }
            },
        ];
    }

    protected function prepareForValidation(): void
    {
        if (
            $this->has(
                'currency_code'
            )
        ) {
            $this->merge([
                'currency_code' =>
                    strtoupper(
                        (string) $this->input(
                            'currency_code'
                        )
                    ),
            ]);
        }
    }
}