<?php

namespace App\Http\Requests\CustomerContact;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreCustomerContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var User|null $user */
        $user = $this->user();

        return $user !== null
            && $user->hasPermission(
                'customer-contact.create'
            );
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'customer_id' => [
                'required',
                'integer',
                Rule::exists(
                    'customers',
                    'id'
                )->whereNull(
                    'deleted_at'
                ),
            ],

            'name' => [
                'required',
                'string',
                'max:150',
            ],

            'designation' => [
                'nullable',
                'string',
                'max:100',
            ],

            'department' => [
                'nullable',
                'string',
                'max:100',
            ],

            'contact_type' => [
                'nullable',
                Rule::in([
                    'general',
                    'sales',
                    'accounts',
                    'management',
                    'support',
                    'purchase',
                    'other',
                ]),
            ],

            'email' => [
                'nullable',
                'email',
                'max:150',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:30',
            ],

            'alternate_phone' => [
                'nullable',
                'string',
                'max:30',
            ],

            'is_primary' => [
                'nullable',
                'boolean',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (
                Validator $validator
            ): void {
                if (
                    $validator
                        ->errors()
                        ->isNotEmpty()
                ) {
                    return;
                }

                $customer =
                    Customer::query()
                        ->find(
                            $this->integer(
                                'customer_id'
                            )
                        );

                if ($customer === null) {
                    return;
                }

                /** @var User $user */
                $user = $this->user();

                $accessibleCustomerExists =
                    Customer::query()
                        ->accessibleTo($user)
                        ->whereKey(
                            $customer->id
                        )
                        ->exists();

                if (
                    ! $accessibleCustomerExists
                ) {
                    $validator
                        ->errors()
                        ->add(
                            'customer_id',
                            'You do not have access to the selected customer.'
                        );
                }
            },
        ];
    }
}