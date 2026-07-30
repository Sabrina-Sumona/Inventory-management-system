<?php

namespace App\Http\Requests\WarehouseLocation;

use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreWarehouseLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(
            'create',
            WarehouseLocation::class
        ) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'warehouse_id' => [
                'required',
                'integer',
                Rule::exists('warehouses', 'id')
                    ->whereNull('deleted_at'),
            ],

            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists(
                    'warehouse_locations',
                    'id'
                )->whereNull('deleted_at'),
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
                Rule::unique(
                    'warehouse_locations',
                    'code'
                )->where(
                    fn ($query) => $query->where(
                        'warehouse_id',
                        $this->integer('warehouse_id')
                    )
                ),
            ],

            'type' => [
                'required',
                'string',
                Rule::in([
                    'zone',
                    'rack',
                    'shelf',
                    'bin',
                ]),
            ],

            'barcode' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique(
                    'warehouse_locations',
                    'barcode'
                ),
            ],

            'capacity' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999999999.999',
                'decimal:0,3',
            ],

            'description' => [
                'nullable',
                'string',
                'max:2000',
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
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $user = $this->user();

                $warehouse = Warehouse::query()
                    ->find($this->integer('warehouse_id'));

                if (
                    ! $warehouse ||
                    ! $user ||
                    ! $user->canAccessWarehouse($warehouse)
                ) {
                    $validator->errors()->add(
                        'warehouse_id',
                        'You do not have access to the selected warehouse.'
                    );

                    return;
                }

                $parentId = $this->input('parent_id');

                if ($parentId === null) {
                    if ($this->input('type') !== 'zone') {
                        $validator->errors()->add(
                            'parent_id',
                            'Only a zone can be created without a parent location.'
                        );
                    }

                    return;
                }

                $parent = WarehouseLocation::query()
                    ->find((int) $parentId);

                if (! $parent) {
                    return;
                }

                if (
                    $parent->warehouse_id !==
                    $warehouse->id
                ) {
                    $validator->errors()->add(
                        'parent_id',
                        'The parent location must belong to the selected warehouse.'
                    );

                    return;
                }

                $expectedParentTypes = [
                    'rack' => 'zone',
                    'shelf' => 'rack',
                    'bin' => 'shelf',
                ];

                $locationType = (string) $this->input(
                    'type'
                );

                $expectedParentType =
                    $expectedParentTypes[$locationType]
                    ?? null;

                if (
                    $expectedParentType === null ||
                    $parent->type !== $expectedParentType
                ) {
                    $validator->errors()->add(
                        'parent_id',
                        "A {$locationType} location must be placed under a {$expectedParentType} location."
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

        if ($this->has('type')) {
            $this->merge([
                'type' => strtolower(
                    trim((string) $this->input('type'))
                ),
            ]);
        }

        foreach ([
            'name',
            'barcode',
            'description',
        ] as $field) {
            if (
                $this->has($field) &&
                is_string($this->input($field))
            ) {
                $value = trim(
                    (string) $this->input($field)
                );

                $this->merge([
                    $field => $value === ''
                        ? null
                        : $value,
                ]);
            }
        }
    }
}