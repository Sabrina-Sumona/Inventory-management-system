<?php

namespace App\Http\Requests\Warehouse;

use App\Models\Warehouse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreWarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(
            'create',
            Warehouse::class
        ) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $companyId = $this->user()?->company_id;

        return [
            'branch_id' => [
                'required',
                'integer',
                Rule::exists('branches', 'id')
                    ->where(
                        fn ($query) => $query
                            ->where('company_id', $companyId)
                            ->whereNull('deleted_at')
                    ),
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Z0-9]+(?:-[A-Z0-9]+)*$/',
                Rule::unique('warehouses', 'code')
                    ->where(
                        fn ($query) => $query
                            ->where('company_id', $companyId)
                    ),
            ],

            'email' => [
                'nullable',
                'email',
                'max:255',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:30',
            ],

            'address' => [
                'nullable',
                'string',
                'max:2000',
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

            'is_primary' => [
                'sometimes',
                'boolean',
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
                    $this->boolean('is_primary')
                    && $this->has('is_active')
                    && ! $this->boolean('is_active')
                ) {
                    $validator->errors()->add(
                        'is_active',
                        'A primary warehouse must be active.'
                    );
                }
            },
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('code')) {
            $this->merge([
                'code' => strtoupper(
                    trim((string) $this->input('code'))
                ),
            ]);
        }

        foreach ([
            'name',
            'email',
            'phone',
            'address',
            'city',
            'district',
            'postal_code',
        ] as $field) {
            if (
                $this->has($field)
                && is_string($this->input($field))
            ) {
                $this->merge([
                    $field => trim(
                        (string) $this->input($field)
                    ),
                ]);
            }
        }
    }
}