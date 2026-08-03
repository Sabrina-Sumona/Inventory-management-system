<?php

namespace App\Http\Requests\CustomerContact;

use App\Models\Customer;
use App\Models\CustomerContact;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateCustomerContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var User|null $user */
        $user = $this->user();

        /** @var CustomerContact|null $customerContact */
        $customerContact = $this->route(
            'customerContact'
        );

        return $user !== null
            && $customerContact !== null
            && $user->can(
                'update',
                $customerContact
            );
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'customer_id' => [
                'sometimes',
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
                'sometimes',
                'required',
                'string',
                'max:150',
            ],

            'designation' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
            ],

            'department' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
            ],

            'contact_type' => [
                'sometimes',
                'required',
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
                'sometimes',
                'nullable',
                'email',
                'max:150',
            ],

            'phone' => [
                'sometimes',
                'nullable',
                'string',
                'max:30',
            ],

            'alternate_phone' => [
                'sometimes',
                'nullable',
                'string',
                'max:30',
            ],

            'is_primary' => [
                'sometimes',
                'boolean',
            ],

            'is_active' => [
                'sometimes',
                'boolean',
            ],

            'notes' => [
                'sometimes',
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

                if (
                    ! $this->exists(
                        'customer_id'
                    )
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