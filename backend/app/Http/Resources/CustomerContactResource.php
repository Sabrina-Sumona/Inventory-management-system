<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerContactResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(
        Request $request
    ): array {
        return [
            'id' =>
                $this->id,

            'customer_id' =>
                $this->customer_id,

            'name' =>
                $this->name,

            'designation' =>
                $this->designation,

            'department' =>
                $this->department,

            'contact_type' =>
                $this->contact_type,

            'email' =>
                $this->email,

            'phone' =>
                $this->phone,

            'alternate_phone' =>
                $this->alternate_phone,

            'is_primary' =>
                $this->is_primary,

            'is_active' =>
                $this->is_active,

            'notes' =>
                $this->notes,

            'customer' =>
                $this->whenLoaded(
                    'customer',
                    fn (): ?array =>
                        $this->customer
                            ? [
                                'id' =>
                                    $this->customer->id,

                                'company_id' =>
                                    $this->customer
                                        ->company_id,

                                'branch_id' =>
                                    $this->customer
                                        ->branch_id,

                                'name' =>
                                    $this->customer->name,

                                'code' =>
                                    $this->customer->code,

                                'business_name' =>
                                    $this->customer
                                        ->business_name,

                                'customer_type' =>
                                    $this->customer
                                        ->customer_type,

                                'is_active' =>
                                    $this->customer
                                        ->is_active,
                            ]
                            : null
                ),

            'creator' =>
                $this->whenLoaded(
                    'creator',
                    fn (): ?array =>
                        $this->creator
                            ? [
                                'id' =>
                                    $this->creator->id,

                                'name' =>
                                    $this->creator->name,

                                'email' =>
                                    $this->creator->email,
                            ]
                            : null
                ),

            'updater' =>
                $this->whenLoaded(
                    'updater',
                    fn (): ?array =>
                        $this->updater
                            ? [
                                'id' =>
                                    $this->updater->id,

                                'name' =>
                                    $this->updater->name,

                                'email' =>
                                    $this->updater->email,
                            ]
                            : null
                ),

            'created_at' =>
                $this->created_at
                    ?->toISOString(),

            'updated_at' =>
                $this->updated_at
                    ?->toISOString(),

            'deleted_at' =>
                $this->deleted_at
                    ?->toISOString(),
        ];
    }
}