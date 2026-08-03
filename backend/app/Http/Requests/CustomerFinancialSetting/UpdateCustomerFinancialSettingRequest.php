<?php

namespace App\Http\Requests\CustomerFinancialSetting;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateCustomerFinancialSettingRequest extends FormRequest
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
        return [
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
                'sometimes',
                'nullable',
                'string',
                'max:5000',
            ],
            'notes' => [
                'sometimes',
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
                $financialSetting =
                    $this->route(
                        'customerFinancialSetting'
                    );

                if (
                    $financialSetting === null
                ) {
                    return;
                }

                $allowCreditSale =
                    $this->has(
                        'allow_credit_sale'
                    )
                        ? $this->boolean(
                            'allow_credit_sale'
                        )
                        : (bool) $financialSetting
                            ->allow_credit_sale;

                $creditLimit =
                    $this->has(
                        'credit_limit'
                    )
                        ? (float) $this->input(
                            'credit_limit'
                        )
                        : (float) $financialSetting
                            ->credit_limit;

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

                if (
                    ! $allowCreditSale
                    && $creditLimit > 0
                ) {
                    $validator
                        ->errors()
                        ->add(
                            'credit_limit',
                            'The credit limit must be zero when credit sales are not allowed.'
                        );
                }

                $isTaxApplicable =
                    $this->has(
                        'is_tax_applicable'
                    )
                        ? $this->boolean(
                            'is_tax_applicable'
                        )
                        : (bool) $financialSetting
                            ->is_tax_applicable;

                $taxPercent =
                    $this->has(
                        'default_tax_percent'
                    )
                        ? (float) $this->input(
                            'default_tax_percent'
                        )
                        : (float) $financialSetting
                            ->default_tax_percent;

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
                    $this->has(
                        'is_withholding_tax_applicable'
                    )
                        ? $this->boolean(
                            'is_withholding_tax_applicable'
                        )
                        : (bool) $financialSetting
                            ->is_withholding_tax_applicable;

                $withholdingTaxPercent =
                    $this->has(
                        'withholding_tax_percent'
                    )
                        ? (float) $this->input(
                            'withholding_tax_percent'
                        )
                        : (float) $financialSetting
                            ->withholding_tax_percent;

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