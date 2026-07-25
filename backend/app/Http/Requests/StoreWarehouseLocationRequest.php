<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWarehouseLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $companyId = $this->integer('company_id');
        $branchId = $this->integer('branch_id');
        $warehouseId = $this->integer('warehouse_id');

        return [
            'company_id' => [
                'required',
                'integer',
                Rule::exists('companies', 'id')
                    ->whereNull('deleted_at'),
            ],

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

            'warehouse_id' => [
                'required',
                'integer',
                Rule::exists('warehouses', 'id')
                    ->where(
                        fn ($query) => $query
                            ->where('company_id', $companyId)
                            ->where('branch_id', $branchId)
                            ->whereNull('deleted_at')
                    ),
            ],

            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('warehouse_locations', 'id')
                    ->where(
                        fn ($query) => $query
                            ->where('warehouse_id', $warehouseId)
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
                Rule::unique('warehouse_locations', 'code')
                    ->where(
                        fn ($query) => $query
                            ->where('warehouse_id', $warehouseId)
                            ->whereNull('deleted_at')
                    ),
            ],

            'type' => [
                'required',
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
                Rule::unique('warehouse_locations', 'barcode')
                    ->whereNull('deleted_at'),
            ],

            'capacity' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'is_active' => [
                'required',
                'boolean',
            ],
        ];
    }
}