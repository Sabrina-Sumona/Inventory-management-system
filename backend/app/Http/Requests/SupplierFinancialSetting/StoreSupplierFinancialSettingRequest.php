<?php

namespace App\Http\Requests\SupplierFinancialSetting;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreSupplierFinancialSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var User|null $user */
        $user = $this->user();

        return $user !== null
            && $user->hasPermission(
                'supplier-financial-setting.create'
            );
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'supplier_id' => [
                'required',
                'integer',
                Rule::exists(
                    'suppliers',
                    'id'
                )->whereNull('deleted_at'),
                Rule::unique(
                    'supplier_financial_settings',
                    'supplier_id'
                ),
            ],

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
            function (Validator $validator): void {
                if (
                    $validator->errors()->has(
                        'supplier_id'
                    )
                ) {
                    return;
                }

                /** @var User|null $user */
                $user = $this->user();

                if ($user === null) {
                    return;
                }

                $supplier = Supplier::query()
                    ->find(
                        $this->integer(
                            'supplier_id'
                        )
                    );

                if ($supplier === null) {
                    return;
                }

                $hasAccess = Supplier::query()
                    ->whereKey($supplier->id)
                    ->accessibleTo($user)
                    ->exists();

                if (! $hasAccess) {
                    $validator->errors()->add(
                        'supplier_id',
                        'The selected supplier is not accessible to this user.'
                    );
                }

                if (
                    $this->boolean(
                        'allow_credit_purchase'
                    )
                    && (float) $this->input(
                        'credit_limit',
                        0
                    ) <= 0
                ) {
                    $validator->errors()->add(
                        'credit_limit',
                        'A positive credit limit is required when credit purchases are enabled.'
                    );
                }

                if (
                    ! $this->boolean(
                        'is_tax_applicable'
                    )
                    && (float) $this->input(
                        'default_tax_percent',
                        0
                    ) > 0
                ) {
                    $validator->errors()->add(
                        'default_tax_percent',
                        'Tax percentage must be zero when tax is not applicable.'
                    );
                }

                if (
                    ! $this->boolean(
                        'is_withholding_tax_applicable'
                    )
                    && (float) $this->input(
                        'withholding_tax_percent',
                        0
                    ) > 0
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
            'supplier_id' => 'supplier',
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