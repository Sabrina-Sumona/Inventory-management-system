<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierContactResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(
        Request $request
    ): array {
        return [
            'id' => $this->id,
            'supplier_id' => $this->supplier_id,

            'name' => $this->name,
            'designation' => $this->designation,
            'department' => $this->department,
            'contact_type' => $this->contact_type,

            'email' => $this->email,
            'phone' => $this->phone,
            'alternate_phone' =>
                $this->alternate_phone,

            'is_primary' => $this->is_primary,
            'is_active' => $this->is_active,

            'notes' => $this->notes,

            'supplier' => $this->whenLoaded(
                'supplier',
                fn (): array => [
                    'id' => $this->supplier->id,
                    'company_id' =>
                        $this->supplier->company_id,
                    'branch_id' =>
                        $this->supplier->branch_id,
                    'name' =>
                        $this->supplier->name,
                    'code' =>
                        $this->supplier->code,
                    'business_name' =>
                        $this->supplier
                            ->business_name,
                    'is_active' =>
                        $this->supplier->is_active,
                ]
            ),

            'creator' => $this->whenLoaded(
                'creator',
                fn (): array => [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                    'email' =>
                        $this->creator->email,
                ]
            ),

            'updater' => $this->whenLoaded(
                'updater',
                fn (): array => [
                    'id' => $this->updater->id,
                    'name' => $this->updater->name,
                    'email' =>
                        $this->updater->email,
                ]
            ),

            'created_at' =>
                $this->created_at?->toISOString(),

            'updated_at' =>
                $this->updated_at?->toISOString(),

            'deleted_at' =>
                $this->deleted_at?->toISOString(),
        ];
    }
}