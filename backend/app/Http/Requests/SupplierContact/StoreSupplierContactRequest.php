<?php

namespace App\Http\Requests\SupplierContact;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreSupplierContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var User|null $user */
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        return $user->hasPermission(
            'supplier-contact.create'
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'supplier_id' => [
                'required',
                'integer',
                Rule::exists(
                    'suppliers',
                    'id'
                )->whereNull('deleted_at'),
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'designation' => [
                'nullable',
                'string',
                'max:100',
            ],

            'department' => [
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
                'nullable',
                'email:rfc',
                'max:255',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:30',
            ],

            'alternate_phone' => [
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
                'nullable',
                'string',
                'max:5000',
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (
                    $validator->errors()->has(
                        'supplier_id'
                    )
                ) {
                    return;
                }

                /** @var User|null $user */
                $user = $this->user();

                if ($user === null) {
                    return;
                }

                $supplier = Supplier::query()
                    ->find(
                        $this->integer(
                            'supplier_id'
                        )
                    );

                if ($supplier === null) {
                    return;
                }

                $hasAccess = Supplier::query()
                    ->whereKey($supplier->id)
                    ->accessibleTo($user)
                    ->exists();

                if (! $hasAccess) {
                    $validator->errors()->add(
                        'supplier_id',
                        'The selected supplier is not accessible to this user.'
                    );
                }
            },
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function attributes(): array
    {
        return [
            'supplier_id' => 'supplier',
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