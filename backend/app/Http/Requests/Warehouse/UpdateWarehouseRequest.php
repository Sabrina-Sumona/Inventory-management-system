<?php

namespace App\Http\Requests\Warehouse;

use App\Models\Warehouse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateWarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $warehouse = $this->route('warehouse');

        return $warehouse instanceof Warehouse
            && $this->user()?->can(
                'update',
                $warehouse
            ) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Warehouse|null $warehouse */
        $warehouse = $this->route('warehouse');

        return [
            'branch_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('branches', 'id')
                    ->where(
                        fn ($query) => $query
                            ->where(
                                'company_id',
                                $warehouse?->company_id
                            )
                            ->whereNull('deleted_at')
                    ),
            ],

            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],

            'code' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                'regex:/^[A-Z0-9]+(?:-[A-Z0-9]+)*$/',
                Rule::unique('warehouses', 'code')
                    ->where(
                        fn ($query) => $query
                            ->where(
                                'company_id',
                                $warehouse?->company_id
                            )
                    )
                    ->ignore($warehouse?->id),
            ],

            'email' => [
                'sometimes',
                'nullable',
                'email',
                'max:255',
            ],

            'phone' => [
                'sometimes',
                'nullable',
                'string',
                'max:30',
            ],

            'address' => [
                'sometimes',
                'nullable',
                'string',
                'max:2000',
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
                /** @var Warehouse|null $warehouse */
                $warehouse = $this->route('warehouse');

                $willBePrimary = $this->has('is_primary')
                    ? $this->boolean('is_primary')
                    : (bool) $warehouse?->is_primary;

                $willBeActive = $this->has('is_active')
                    ? $this->boolean('is_active')
                    : (bool) $warehouse?->is_active;

                if ($willBePrimary && ! $willBeActive) {
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