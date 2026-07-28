<?php

namespace App\Http\Requests\Branch;

use App\Models\Branch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        $branch = $this->route('branch');

        return $branch instanceof Branch
            && $this->user()?->can(
                'update',
                $branch
            ) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Branch|null $branch */
        $branch = $this->route('branch');

        return [
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
                Rule::unique('branches', 'code')
                    ->where(
                        fn ($query) => $query
                            ->where(
                                'company_id',
                                $branch?->company_id
                            )
                    )
                    ->ignore($branch?->id),
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