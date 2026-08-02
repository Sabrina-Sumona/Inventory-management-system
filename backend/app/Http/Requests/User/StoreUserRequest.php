<?php

namespace App\Http\Requests\User;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows(
            'create',
            User::class
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'company_id' => [
                'nullable',
                'integer',
                Rule::exists(
                    'companies',
                    'id'
                ),
            ],

            'name' => [
                'required',
                'string',
                'max:150',
            ],

            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(
                    'users',
                    'email'
                ),
            ],

            'password' => [
                'required',
                'confirmed',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers(),
            ],

            'role_ids' => [
                'required',
                'array',
                'min:1',
            ],

            'role_ids.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists(
                    'roles',
                    'id'
                ),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'role_ids.required' =>
                'At least one role must be selected.',

            'role_ids.min' =>
                'At least one role must be selected.',

            'password.confirmed' =>
                'The password confirmation does not match.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => is_string(
                $this->name
            )
                ? trim($this->name)
                : $this->name,

            'email' => is_string(
                $this->email
            )
                ? strtolower(
                    trim($this->email)
                )
                : $this->email,
        ]);
    }
}