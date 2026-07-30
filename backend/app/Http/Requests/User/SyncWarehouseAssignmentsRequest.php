<?php

namespace App\Http\Requests\User;

use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SyncWarehouseAssignmentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var User|null $authenticatedUser */
        $authenticatedUser = $this->user();

        /** @var User|null $targetUser */
        $targetUser = $this->route('user');

        return $authenticatedUser !== null
            && $targetUser !== null
            && $authenticatedUser->can(
                'manageAssignments',
                $targetUser
            );
    }

    public function rules(): array
    {
        /** @var User|null $targetUser */
        $targetUser = $this->route('user');

        return [
            'warehouse_ids' => [
                'required',
                'array',
            ],

            'warehouse_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('warehouses', 'id')
                    ->where(
                        fn ($query) => $query
                            ->whereNull('deleted_at')
                            ->when(
                                $targetUser?->company_id !== null,
                                fn ($warehouseQuery) => $warehouseQuery
                                    ->where(
                                        'company_id',
                                        $targetUser->company_id
                                    )
                            )
                    ),
            ],

            'primary_warehouse_id' => [
                'nullable',
                'integer',
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $warehouseIds = array_map(
                    'intval',
                    $this->input('warehouse_ids', [])
                );

                $primaryWarehouseId =
                    $this->input('primary_warehouse_id');

                if (
                    $primaryWarehouseId !== null
                    && ! in_array(
                        (int) $primaryWarehouseId,
                        $warehouseIds,
                        true
                    )
                ) {
                    $validator->errors()->add(
                        'primary_warehouse_id',
                        'The primary warehouse must be included in warehouse_ids.'
                    );
                }

                /** @var User|null $targetUser */
                $targetUser = $this->route('user');

                if (
                    $targetUser?->company_id === null
                    && $warehouseIds !== []
                ) {
                    $validator->errors()->add(
                        'warehouse_ids',
                        'Global users do not require warehouse assignments.'
                    );

                    return;
                }

                if (
                    $targetUser?->company_id === null
                    || $warehouseIds === []
                ) {
                    return;
                }

                $warehouses = Warehouse::query()
                    ->where(
                        'company_id',
                        $targetUser->company_id
                    )
                    ->whereIn('id', $warehouseIds)
                    ->whereNull('deleted_at')
                    ->get([
                        'id',
                        'branch_id',
                    ]);

                if ($warehouses->count() !== count($warehouseIds)) {
                    $validator->errors()->add(
                        'warehouse_ids',
                        'One or more warehouses are invalid for this user.'
                    );

                    return;
                }

                $assignedBranchIds = $targetUser
                    ->branches()
                    ->pluck('branches.id')
                    ->map(
                        fn ($branchId) => (int) $branchId
                    )
                    ->all();

                foreach ($warehouses as $warehouse) {
                    if (
                        ! in_array(
                            (int) $warehouse->branch_id,
                            $assignedBranchIds,
                            true
                        )
                    ) {
                        $validator->errors()->add(
                            'warehouse_ids',
                            'The user must be assigned to every selected warehouse branch first.'
                        );

                        break;
                    }
                }
            },
        ];
    }
}