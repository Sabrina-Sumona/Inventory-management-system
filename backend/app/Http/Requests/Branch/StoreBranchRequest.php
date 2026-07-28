<?php

namespace App\Http\Requests\Branch;

use App\Models\Branch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(
            'create',
            Branch::class
        ) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $companyId = $this->user()?->company_id;

        return [
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
                Rule::unique('branches', 'code')
                    ->where(
                        fn ($query) => $query
                            ->where(
                                'company_id',
                                $companyId
                            )
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

            'is_head_office' => [
                'sometimes',
                'boolean',
            ],

            'is_active' => [
                'sometimes',
                'boolean',
            ],
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