<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BranchResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'name' => $this->name,
            'code' => $this->code,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'city' => $this->city,
            'district' => $this->district,
            'postal_code' => $this->postal_code,
            'is_head_office' => (bool) $this->is_head_office,
            'is_active' => (bool) $this->is_active,

            'company' => $this->whenLoaded(
                'company',
                fn (): array => [
                    'id' => $this->company->id,
                    'name' => $this->company->name,
                    'code' => $this->company->code,
                ]
            ),

            'warehouses_count' => $this->whenCounted(
                'warehouses'
            ),

            'users_count' => $this->whenCounted(
                'users'
            ),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'deleted_at' => $this->deleted_at?->toISOString(),
        ];
    }
}