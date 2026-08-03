<?php

namespace App\Http\Requests\Customer;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var User|null $user */
        $user = $this->user();

        /** @var Customer|null $customer */
        $customer = $this->route(
            'customer'
        );

        return $user !== null
            && $customer !== null
            && $user->can(
                'update',
                $customer
            );
    }

    protected function prepareForValidation(): void
    {
        /** @var Customer|null $customer */
        $customer = $this->route(
            'customer'
        );

        if ($customer !== null) {
            $this->merge([
                'company_id' =>
                    $customer->company_id,
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Customer|null $customer */
        $customer = $this->route(
            'customer'
        );

        return [
            'company_id' => [
                'required',
                'integer',
                Rule::exists(
                    'companies',
                    'id'
                )->whereNull(
                    'deleted_at'
                ),
            ],

            'branch_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists(
                    'branches',
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

            'code' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique(
                    'customers',
                    'code'
                )
                    ->where(
                        fn ($query) =>
                            $query
                                ->where(
                                    'company_id',
                                    $this->integer(
                                        'company_id'
                                    )
                                )
                                ->whereNull(
                                    'deleted_at'
                                )
                    )
                    ->ignore(
                        $customer?->id
                    ),
            ],

            'business_name' => [
                'sometimes',
                'nullable',
                'string',
                'max:150',
            ],

            'customer_type' => [
                'sometimes',
                'required',
                Rule::in([
                    'retail',
                    'wholesale',
                    'corporate',
                    'dealer',
                    'government',
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

            'website' => [
                'sometimes',
                'nullable',
                'url',
                'max:255',
            ],

            'tax_identification_number' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
            ],

            'trade_license_number' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
            ],

            'billing_address_line_1' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],

            'billing_address_line_2' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],

            'billing_city' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
            ],

            'billing_district' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
            ],

            'billing_postal_code' => [
                'sometimes',
                'nullable',
                'string',
                'max:20',
            ],

            'billing_country' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
            ],

            'shipping_address_line_1' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],

            'shipping_address_line_2' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],

            'shipping_city' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
            ],

            'shipping_district' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
            ],

            'shipping_postal_code' => [
                'sometimes',
                'nullable',
                'string',
                'max:20',
            ],

            'shipping_country' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
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

            'opening_balance' => [
                'sometimes',
                'numeric',
                'min:0',
                'max:9999999999999999.99',
            ],

            'opening_balance_type' => [
                'sometimes',
                Rule::in([
                    'receivable',
                    'payable',
                ]),
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
                if (
                    $validator
                        ->errors()
                        ->isNotEmpty()
                ) {
                    return;
                }

                if (
                    ! $this->exists(
                        'branch_id'
                    )
                ) {
                    return;
                }

                $branchId =
                    $this->input(
                        'branch_id'
                    );

                if ($branchId === null) {
                    return;
                }

                $branch =
                    Branch::query()
                        ->find($branchId);

                if ($branch === null) {
                    return;
                }

                if (
                    (int) $branch->company_id
                    !== $this->integer(
                        'company_id'
                    )
                ) {
                    $validator
                        ->errors()
                        ->add(
                            'branch_id',
                            'The selected branch does not belong to the customer company.'
                        );

                    return;
                }

                /** @var User $user */
                $user = $this->user();

                if (
                    ! $user->isSuperAdmin()
                    && ! $user->canAccessBranch(
                        $branch
                    )
                ) {
                    $validator
                        ->errors()
                        ->add(
                            'branch_id',
                            'You do not have access to the selected branch.'
                        );
                }
            },
        ];
    }
}