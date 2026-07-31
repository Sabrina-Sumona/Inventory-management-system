<?php

namespace App\Http\Requests\Supplier;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var User|null $user */
        $user = $this->user();

        return $user !== null
            && $user->hasPermission(
                'supplier.create'
            );
    }

    protected function prepareForValidation(): void
    {
        /** @var User|null $user */
        $user = $this->user();

        if (
            $user !== null
            && ! $user->isSuperAdmin()
        ) {
            $this->merge([
                'company_id' =>
                    $user->company_id,
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'company_id' => [
                'required',
                'integer',
                Rule::exists(
                    'companies',
                    'id'
                )->whereNull('deleted_at'),
            ],

            'branch_id' => [
                'nullable',
                'integer',
                Rule::exists(
                    'branches',
                    'id'
                )->whereNull('deleted_at'),
            ],

            'name' => [
                'required',
                'string',
                'max:150',
            ],

            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique(
                    'suppliers',
                    'code'
                )
                    ->where(
                        fn ($query) => $query
                            ->where(
                                'company_id',
                                $this->integer(
                                    'company_id'
                                )
                            )
                            ->whereNull(
                                'deleted_at'
                            )
                    ),
            ],

            'business_name' => [
                'nullable',
                'string',
                'max:150',
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

            'website' => [
                'nullable',
                'url',
                'max:255',
            ],

            'tax_identification_number' => [
                'nullable',
                'string',
                'max:100',
            ],

            'trade_license_number' => [
                'nullable',
                'string',
                'max:100',
            ],

            'address_line_1' => [
                'nullable',
                'string',
                'max:255',
            ],

            'address_line_2' => [
                'nullable',
                'string',
                'max:255',
            ],

            'city' => [
                'nullable',
                'string',
                'max:100',
            ],

            'district' => [
                'nullable',
                'string',
                'max:100',
            ],

            'postal_code' => [
                'nullable',
                'string',
                'max:20',
            ],

            'country' => [
                'nullable',
                'string',
                'max:100',
            ],

            'payment_term_days' => [
                'nullable',
                'integer',
                'min:0',
                'max:3650',
            ],

            'credit_limit' => [
                'nullable',
                'numeric',
                'min:0',
                'max:9999999999999999.99',
            ],

            'opening_balance' => [
                'nullable',
                'numeric',
                'min:0',
                'max:9999999999999999.99',
            ],

            'opening_balance_type' => [
                'nullable',
                Rule::in([
                    'payable',
                    'receivable',
                ]),
            ],

            'notes' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'is_active' => [
                'nullable',
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
                    $validator->errors()->isNotEmpty()
                ) {
                    return;
                }

                $branchId = $this->input(
                    'branch_id'
                );

                if ($branchId === null) {
                    return;
                }

                $branch = Branch::query()
                    ->find($branchId);

                if ($branch === null) {
                    return;
                }

                if (
                    (int) $branch->company_id !==
                    $this->integer('company_id')
                ) {
                    $validator->errors()->add(
                        'branch_id',
                        'The selected branch does not belong to the supplier company.'
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
                    $validator->errors()->add(
                        'branch_id',
                        'You do not have access to the selected branch.'
                    );
                }
            },
        ];
    }
}