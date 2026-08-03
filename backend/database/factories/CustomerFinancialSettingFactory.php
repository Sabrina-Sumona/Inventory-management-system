<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\CustomerFinancialSetting;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerFinancialSetting>
 */
class CustomerFinancialSettingFactory extends Factory
{
    protected $model =
        CustomerFinancialSetting::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $allowCreditSale =
            fake()->boolean();

        $isTaxApplicable =
            fake()->boolean();

        $isWithholdingTaxApplicable =
            fake()->boolean();

        return [
            'customer_id' =>
                Customer::factory(),

            'currency_code' =>
                'BDT',

            'default_payment_method' =>
                fake()->randomElement([
                    'cash',
                    'bank_transfer',
                    'mobile_banking',
                    'cheque',
                    'card',
                    'credit',
                ]),

            'payment_term_days' =>
                fake()->randomElement([
                    0,
                    7,
                    15,
                    30,
                    45,
                    60,
                ]),

            'credit_limit' =>
                $allowCreditSale
                    ? fake()->randomFloat(
                        2,
                        1000,
                        1000000
                    )
                    : 0,

            'allow_credit_sale' =>
                $allowCreditSale,

            'block_sale_on_credit_limit' =>
                true,

            'default_sales_discount_percent' =>
                fake()->randomFloat(
                    2,
                    0,
                    25
                ),

            'is_tax_applicable' =>
                $isTaxApplicable,

            'default_tax_percent' =>
                $isTaxApplicable
                    ? fake()->randomFloat(
                        2,
                        1,
                        15
                    )
                    : 0,

            'is_withholding_tax_applicable' =>
                $isWithholdingTaxApplicable,

            'withholding_tax_percent' =>
                $isWithholdingTaxApplicable
                    ? fake()->randomFloat(
                        2,
                        1,
                        10
                    )
                    : 0,

            'sales_price_basis' =>
                fake()->randomElement([
                    'exclusive_of_tax',
                    'inclusive_of_tax',
                ]),

            'default_sales_order_term' =>
                fake()->randomElement([
                    'standard',
                    'advance_payment',
                    'cash_on_delivery',
                    'partial_advance',
                    'credit',
                ]),

            'payment_instruction' =>
                fake()
                    ->optional()
                    ->sentence(),

            'notes' =>
                fake()
                    ->optional()
                    ->sentence(),

            'is_active' =>
                true,

            'created_by' =>
                null,

            'updated_by' =>
                null,
        ];
    }

    public function forCustomer(
        Customer $customer
    ): static {
        return $this->state(
            fn (): array => [
                'customer_id' =>
                    $customer->id,
            ]
        );
    }

    public function createdBy(
        User $user
    ): static {
        return $this->state(
            fn (): array => [
                'created_by' =>
                    $user->id,

                'updated_by' =>
                    $user->id,
            ]
        );
    }

    public function creditEnabled(
        float $creditLimit = 100000
    ): static {
        return $this->state(
            fn (): array => [
                'allow_credit_sale' =>
                    true,

                'credit_limit' =>
                    $creditLimit,
            ]
        );
    }

    public function taxable(
        float $taxPercent = 15
    ): static {
        return $this->state(
            fn (): array => [
                'is_tax_applicable' =>
                    true,

                'default_tax_percent' =>
                    $taxPercent,
            ]
        );
    }

    public function inactive(): static
    {
        return $this->state(
            fn (): array => [
                'is_active' =>
                    false,
            ]
        );
    }
}