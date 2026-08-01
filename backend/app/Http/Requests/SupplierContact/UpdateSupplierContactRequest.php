<?php

namespace App\Http\Requests\SupplierContact;

use App\Models\SupplierContact;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSupplierContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var User|null $user */
        $user = $this->user();

        $supplierContact =
            $this->route('supplierContact')
            ?? $this->route(
                'supplier_contact'
            );

        if (
            $user === null
            || ! $supplierContact instanceof
                SupplierContact
        ) {
            return false;
        }

        return $user->can(
            'update',
            $supplierContact
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],

            'designation' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
            ],

            'department' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
            ],

            'contact_type' => [
                'sometimes',
                'required',
                Rule::in([
                    'general',
                    'sales',
                    'accounts',
                    'support',
                    'management',
                ]),
            ],

            'email' => [
                'sometimes',
                'nullable',
                'email:rfc',
                'max:255',
            ],

            'phone' => [
                'sometimes',
                'nullable',
                'string',
                'max:30',
            ],

            'alternate_phone' => [
                'sometimes',
                'nullable',
                'string',
                'max:30',
            ],

            'is_primary' => [
                'sometimes',
                'boolean',
            ],

            'is_active' => [
                'sometimes',
                'boolean',
            ],

            'notes' => [
                'sometimes',
                'nullable',
                'string',
                'max:5000',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function attributes(): array
    {
        return [
            'contact_type' => 'contact type',
            'alternate_phone' =>
                'alternate phone',
            'is_primary' =>
                'primary contact status',
            'is_active' =>
                'active status',
        ];
    }
}