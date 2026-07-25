<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWarehouseRequest extends FormRequest
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

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('warehouses', 'code')
                    ->where(
                        fn ($query) => $query
                            ->where('company_id', $companyId)
                            ->whereNull('deleted_at')
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
                'required',
                'boolean',
            ],

            'is_active' => [
                'required',
                'boolean',
            ],
        ];
    }
}