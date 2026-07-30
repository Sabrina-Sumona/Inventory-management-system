<?php

namespace App\Http\Requests\WarehouseLocation;

use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateWarehouseLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $location = $this->route(
            'warehouseLocation'
        );

        return $location instanceof WarehouseLocation
            && $this->user()?->can(
                'update',
                $location
            ) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var WarehouseLocation|null $location */
        $location = $this->route(
            'warehouseLocation'
        );

        $warehouseId = $this->integer(
            'warehouse_id',
            $location?->warehouse_id ?? 0
        );

        return [
            'warehouse_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('warehouses', 'id')
                    ->whereNull('deleted_at'),
            ],

            'parent_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists(
                    'warehouse_locations',
                    'id'
                )
                    ->whereNull('deleted_at')
                    ->where(
                        fn ($query) => $query
                            ->whereNot(
                                'id',
                                $location?->id
                            )
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
                Rule::unique(
                    'warehouse_locations',
                    'code'
                )
                    ->where(
                        fn ($query) => $query->where(
                            'warehouse_id',
                            $warehouseId
                        )
                    )
                    ->ignore($location?->id),
            ],

            'type' => [
                'sometimes',
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
                'sometimes',
                'nullable',
                'string',
                'max:100',
                Rule::unique(
                    'warehouse_locations',
                    'barcode'
                )->ignore($location?->id),
            ],

            'capacity' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
                'max:999999999999.999',
                'decimal:0,3',
            ],

            'description' => [
                'sometimes',
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

                /** @var WarehouseLocation|null $location */
                $location = $this->route(
                    'warehouseLocation'
                );

                if (! $location) {
                    return;
                }

                $warehouseId = $this->has(
                    'warehouse_id'
                )
                    ? $this->integer('warehouse_id')
                    : $location->warehouse_id;

                $warehouse = Warehouse::query()
                    ->find($warehouseId);

                if (
                    ! $warehouse ||
                    ! $this->user()?->canAccessWarehouse(
                        $warehouse
                    )
                ) {
                    $validator->errors()->add(
                        'warehouse_id',
                        'You do not have access to the selected warehouse.'
                    );

                    return;
                }

                $type = $this->has('type')
                    ? (string) $this->input('type')
                    : $location->type;

                $parentId = $this->has('parent_id')
                    ? $this->input('parent_id')
                    : $location->parent_id;

                if ($parentId === null) {
                    if ($type !== 'zone') {
                        $validator->errors()->add(
                            'parent_id',
                            'Only a zone can exist without a parent location.'
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

                if (
                    $this->isDescendant(
                        $parent,
                        $location
                    )
                ) {
                    $validator->errors()->add(
                        'parent_id',
                        'A location cannot be moved under one of its own descendants.'
                    );

                    return;
                }

                $expectedParentTypes = [
                    'rack' => 'zone',
                    'shelf' => 'rack',
                    'bin' => 'shelf',
                ];

                $expectedParentType =
                    $expectedParentTypes[$type]
                    ?? null;

                if (
                    $expectedParentType === null ||
                    $parent->type !== $expectedParentType
                ) {
                    $validator->errors()->add(
                        'parent_id',
                        "A {$type} location must be placed under a {$expectedParentType} location."
                    );
                }
            },
        ];
    }

    private function isDescendant(
        WarehouseLocation $parent,
        WarehouseLocation $location
    ): bool {
        $current = $parent;

        while ($current->parent_id !== null) {
            if ($current->parent_id === $location->id) {
                return true;
            }

            $current = WarehouseLocation::query()
                ->find($current->parent_id);

            if (! $current) {
                break;
            }
        }

        return false;
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