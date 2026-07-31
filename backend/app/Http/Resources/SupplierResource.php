<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierResource extends JsonResource
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

            'name' => $this->name,
            'code' => $this->code,
            'business_name' => $this->business_name,

            'email' => $this->email,
            'phone' => $this->phone,
            'alternate_phone' => $this->alternate_phone,
            'website' => $this->website,

            'tax_identification_number' =>
                $this->tax_identification_number,

            'trade_license_number' =>
                $this->trade_license_number,

            'address_line_1' => $this->address_line_1,
            'address_line_2' => $this->address_line_2,
            'city' => $this->city,
            'district' => $this->district,
            'postal_code' => $this->postal_code,
            'country' => $this->country,

            'payment_term_days' =>
                $this->payment_term_days,

            'credit_limit' =>
                $this->credit_limit,

            'opening_balance' =>
                $this->opening_balance,

            'opening_balance_type' =>
                $this->opening_balance_type,

            'notes' => $this->notes,
            'is_active' => $this->is_active,

            'company' => $this->whenLoaded(
                'company',
                fn (): ?array => $this->company
                    ? [
                        'id' => $this->company->id,
                        'name' => $this->company->name,
                        'code' => $this->company->code,
                    ]
                    : null
            ),

            'branch' => $this->whenLoaded(
                'branch',
                fn (): ?array => $this->branch
                    ? [
                        'id' => $this->branch->id,
                        'name' => $this->branch->name,
                        'code' => $this->branch->code,
                        'city' => $this->branch->city,
                        'district' => $this->branch->district,
                        'is_head_office' =>
                            $this->branch->is_head_office,
                        'is_active' =>
                            $this->branch->is_active,
                    ]
                    : null
            ),

            'creator' => $this->whenLoaded(
                'creator',
                fn (): ?array => $this->creator
                    ? [
                        'id' => $this->creator->id,
                        'name' => $this->creator->name,
                        'email' => $this->creator->email,
                    ]
                    : null
            ),

            'updater' => $this->whenLoaded(
                'updater',
                fn (): ?array => $this->updater
                    ? [
                        'id' => $this->updater->id,
                        'name' => $this->updater->name,
                        'email' => $this->updater->email,
                    ]
                    : null
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