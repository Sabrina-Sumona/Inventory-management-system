<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WarehouseLocationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'branch_id' => $this->branch_id,
            'warehouse_id' => $this->warehouse_id,
            'parent_id' => $this->parent_id,

            'name' => $this->name,
            'code' => $this->code,
            'type' => $this->type,
            'barcode' => $this->barcode,
            'capacity' => $this->capacity,
            'description' => $this->description,
            'is_active' => (bool) $this->is_active,

            'company' => $this->whenLoaded(
                'company',
                fn (): array => [
                    'id' => $this->company->id,
                    'name' => $this->company->name,
                    'code' => $this->company->code,
                ]
            ),

            'branch' => $this->whenLoaded(
                'branch',
                fn (): array => [
                    'id' => $this->branch->id,
                    'name' => $this->branch->name,
                    'code' => $this->branch->code,
                ]
            ),

            'warehouse' => $this->whenLoaded(
                'warehouse',
                fn (): array => [
                    'id' => $this->warehouse->id,
                    'name' => $this->warehouse->name,
                    'code' => $this->warehouse->code,
                    'is_primary' => (bool) $this->warehouse->is_primary,
                ]
            ),

            'parent' => $this->whenLoaded(
                'parent',
                fn (): ?array => $this->parent
                    ? [
                        'id' => $this->parent->id,
                        'name' => $this->parent->name,
                        'code' => $this->parent->code,
                        'type' => $this->parent->type,
                    ]
                    : null
            ),

            'children_count' => $this->whenCounted(
                'children'
            ),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'deleted_at' => $this->deleted_at?->toISOString(),
        ];
    }
}