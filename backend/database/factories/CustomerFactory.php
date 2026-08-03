<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' =>
                Company::factory(),

            'branch_id' => null,

            'name' =>
                fake()->name(),

            'code' =>
                strtoupper(
                    fake()
                        ->unique()
                        ->bothify('CUS-####')
                ),

            'business_name' =>
                fake()->optional()->company(),

            'customer_type' =>
                fake()->randomElement([
                    'retail',
                    'wholesale',
                    'corporate',
                    'dealer',
                    'government',
                    'other',
                ]),

            'email' =>
                fake()
                    ->optional()
                    ->safeEmail(),

            'phone' =>
                fake()
                    ->optional()
                    ->numerify('01#########'),

            'alternate_phone' =>
                fake()
                    ->optional()
                    ->numerify('01#########'),

            'website' =>
                fake()
                    ->optional()
                    ->url(),

            'tax_identification_number' =>
                fake()
                    ->optional()
                    ->numerify('TIN########'),

            'trade_license_number' =>
                fake()
                    ->optional()
                    ->bothify('TL-####-????'),

            'billing_address_line_1' =>
                fake()
                    ->optional()
                    ->streetAddress(),

            'billing_address_line_2' =>
                fake()
                    ->optional()
                    ->secondaryAddress(),

            'billing_city' =>
                fake()
                    ->optional()
                    ->city(),

            'billing_district' =>
                fake()
                    ->optional()
                    ->city(),

            'billing_postal_code' =>
                fake()
                    ->optional()
                    ->postcode(),

            'billing_country' =>
                'Bangladesh',

            'shipping_address_line_1' =>
                fake()
                    ->optional()
                    ->streetAddress(),

            'shipping_address_line_2' =>
                fake()
                    ->optional()
                    ->secondaryAddress(),

            'shipping_city' =>
                fake()
                    ->optional()
                    ->city(),

            'shipping_district' =>
                fake()
                    ->optional()
                    ->city(),

            'shipping_postal_code' =>
                fake()
                    ->optional()
                    ->postcode(),

            'shipping_country' =>
                'Bangladesh',

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
                fake()->randomFloat(
                    2,
                    0,
                    1000000
                ),

            'opening_balance' =>
                fake()->randomFloat(
                    2,
                    0,
                    100000
                ),

            'opening_balance_type' =>
                'receivable',

            'notes' =>
                fake()
                    ->optional()
                    ->sentence(),

            'is_active' => true,

            'created_by' => null,

            'updated_by' => null,
        ];
    }

    public function forCompany(
        Company $company
    ): static {
        return $this->state(
            fn (): array => [
                'company_id' =>
                    $company->id,
            ]
        );
    }

    public function forBranch(
        Branch $branch
    ): static {
        return $this->state(
            fn (): array => [
                'company_id' =>
                    $branch->company_id,

                'branch_id' =>
                    $branch->id,
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

    public function inactive(): static
    {
        return $this->state(
            fn (): array => [
                'is_active' => false,
            ]
        );
    }
}