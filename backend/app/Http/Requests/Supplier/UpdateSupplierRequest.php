<?php

namespace App\Http\Requests\Supplier;

use App\Models\Branch;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var User|null $user */
        $user = $this->user();

        /** @var Supplier|null $supplier */
        $supplier = $this->route(
            'supplier'
        );

        return $user !== null
            && $supplier !== null
            && $user->can(
                'update',
                $supplier
            );
    }

    protected function prepareForValidation(): void
    {
        /** @var Supplier|null $supplier */
        $supplier = $this->route(
            'supplier'
        );

        if ($supplier !== null) {
            $this->merge([
                'company_id' =>
                    $supplier->company_id,
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Supplier|null $supplier */
        $supplier = $this->route(
            'supplier'
        );

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
                'sometimes',
                'nullable',
                'integer',
                Rule::exists(
                    'branches',
                    'id'
                )->whereNull('deleted_at'),
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
                    )
                    ->ignore($supplier?->id),
            ],

            'business_name' => [
                'sometimes',
                'nullable',
                'string',
                'max:150',
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

            'address_line_1' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],

            'address_line_2' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],

            'city' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
            ],

            'district' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
            ],

            'postal_code' => [
                'sometimes',
                'nullable',
                'string',
                'max:20',
            ],

            'country' => [
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
                    'payable',
                    'receivable',
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
                    $validator->errors()->isNotEmpty()
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