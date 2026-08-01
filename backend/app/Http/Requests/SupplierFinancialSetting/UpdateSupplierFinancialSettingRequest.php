<?php

namespace App\Http\Requests\SupplierFinancialSetting;

use App\Models\SupplierFinancialSetting;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateSupplierFinancialSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var User|null $user */
        $user = $this->user();

        $financialSetting =
            $this->route(
                'supplierFinancialSetting'
            )
            ?? $this->route(
                'supplier_financial_setting'
            );

        if (
            $user === null
            || ! $financialSetting instanceof
                SupplierFinancialSetting
        ) {
            return false;
        }

        return $user->can(
            'update',
            $financialSetting
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'currency_code' => [
                'sometimes',
                'required',
                'string',
                'size:3',
            ],

            'default_payment_method' => [
                'sometimes',
                'required',
                Rule::in([
                    'cash',
                    'bank_transfer',
                    'cheque',
                    'mobile_banking',
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

            'allow_credit_purchase' => [
                'sometimes',
                'boolean',
            ],

            'block_purchase_on_credit_limit' => [
                'sometimes',
                'boolean',
            ],

            'default_purchase_discount_percent' => [
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

            'purchase_price_basis' => [
                'sometimes',
                'required',
                Rule::in([
                    'inclusive_of_tax',
                    'exclusive_of_tax',
                ]),
            ],

            'default_purchase_order_term' => [
                'sometimes',
                'required',
                Rule::in([
                    'standard',
                    'advance_payment',
                    'partial_advance',
                    'cash_on_delivery',
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
            function (Validator $validator): void {
                $financialSetting =
                    $this->route(
                        'supplierFinancialSetting'
                    )
                    ?? $this->route(
                        'supplier_financial_setting'
                    );

                if (
                    ! $financialSetting instanceof
                        SupplierFinancialSetting
                ) {
                    return;
                }

                $allowCreditPurchase =
                    $this->has(
                        'allow_credit_purchase'
                    )
                        ? $this->boolean(
                            'allow_credit_purchase'
                        )
                        : $financialSetting
                            ->allow_credit_purchase;

                $creditLimit =
                    $this->has('credit_limit')
                        ? (float) $this->input(
                            'credit_limit'
                        )
                        : (float) $financialSetting
                            ->credit_limit;

                if (
                    $allowCreditPurchase
                    && $creditLimit <= 0
                ) {
                    $validator->errors()->add(
                        'credit_limit',
                        'A positive credit limit is required when credit purchases are enabled.'
                    );
                }

                $isTaxApplicable =
                    $this->has(
                        'is_tax_applicable'
                    )
                        ? $this->boolean(
                            'is_tax_applicable'
                        )
                        : $financialSetting
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
                    $validator->errors()->add(
                        'default_tax_percent',
                        'Tax percentage must be zero when tax is not applicable.'
                    );
                }

                $isWithholdingTaxApplicable =
                    $this->has(
                        'is_withholding_tax_applicable'
                    )
                        ? $this->boolean(
                            'is_withholding_tax_applicable'
                        )
                        : $financialSetting
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
                    $validator->errors()->add(
                        'withholding_tax_percent',
                        'Withholding tax percentage must be zero when withholding tax is not applicable.'
                    );
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'currency_code' => 'currency code',
            'default_payment_method' =>
                'default payment method',
            'payment_term_days' =>
                'payment term days',
            'credit_limit' => 'credit limit',
            'allow_credit_purchase' =>
                'credit purchase status',
            'block_purchase_on_credit_limit' =>
                'credit limit blocking status',
            'default_purchase_discount_percent' =>
                'default purchase discount percentage',
            'is_tax_applicable' =>
                'tax applicability',
            'default_tax_percent' =>
                'default tax percentage',
            'is_withholding_tax_applicable' =>
                'withholding tax applicability',
            'withholding_tax_percent' =>
                'withholding tax percentage',
            'purchase_price_basis' =>
                'purchase price basis',
            'default_purchase_order_term' =>
                'default purchase order term',
            'payment_instruction' =>
                'payment instruction',
            'is_active' => 'active status',
        ];
    }
}