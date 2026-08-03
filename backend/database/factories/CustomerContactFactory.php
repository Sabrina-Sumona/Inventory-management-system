<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\CustomerContact;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerContact>
 */
class CustomerContactFactory extends Factory
{
    protected $model =
        CustomerContact::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' =>
                Customer::factory(),

            'name' =>
                fake()->name(),

            'designation' =>
                fake()
                    ->optional()
                    ->jobTitle(),

            'department' =>
                fake()
                    ->optional()
                    ->randomElement([
                        'Sales',
                        'Accounts',
                        'Management',
                        'Support',
                        'Purchase',
                    ]),

            'contact_type' =>
                fake()->randomElement([
                    'general',
                    'sales',
                    'accounts',
                    'management',
                    'support',
                    'purchase',
                    'other',
                ]),

            'email' =>
                fake()
                    ->optional()
                    ->safeEmail(),

            'phone' =>
                fake()
                    ->optional()
                    ->numerify(
                        '01#########'
                    ),

            'alternate_phone' =>
                fake()
                    ->optional()
                    ->numerify(
                        '01#########'
                    ),

            'is_primary' =>
                false,

            'is_active' =>
                true,

            'notes' =>
                fake()
                    ->optional()
                    ->sentence(),

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

    public function primary(): static
    {
        return $this->state(
            fn (): array => [
                'is_primary' =>
                    true,
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