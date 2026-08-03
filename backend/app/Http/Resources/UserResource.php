<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(
        Request $request
    ): array {
        return [
            'id' => $this->id,

            'company_id' =>
                $this->company_id,

            'name' => $this->name,

            'email' => $this->email,

            'is_active' =>
                (bool) $this->is_active,

            'company' => $this->whenLoaded(
                'company',
                fn (): ?array =>
                    $this->company
                        ? [
                            'id' =>
                                $this->company->id,

                            'name' =>
                                $this->company->name,

                            'code' =>
                                $this->company->code,
                        ]
                        : null
            ),

            'roles' => $this->whenLoaded(
                'roles',
                fn () => $this->roles
                    ->map(
                        fn ($role): array => [
                            'id' =>
                                $role->id,

                            'name' =>
                                $role->name,

                            'code' =>
                                $role->code,
                        ]
                    )
                    ->values()
            ),

            'branches_count' =>
                $this->whenCounted(
                    'branches'
                ),

            'warehouses_count' =>
                $this->whenCounted(
                    'warehouses'
                ),

            'created_at' =>
                $this->created_at
                    ?->toISOString(),

            'updated_at' =>
                $this->updated_at
                    ?->toISOString(),
        ];
    }
}