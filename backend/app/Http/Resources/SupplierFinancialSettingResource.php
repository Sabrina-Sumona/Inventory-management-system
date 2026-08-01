<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierFinancialSettingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(
        Request $request
    ): array {
        return [
            'id' => $this->id,
            'supplier_id' => $this->supplier_id,

            'currency_code' => $this->currency_code,
            'default_payment_method' =>
                $this->default_payment_method,

            'payment_term_days' =>
                $this->payment_term_days,

            'credit_limit' =>
                $this->credit_limit,

            'allow_credit_purchase' =>
                $this->allow_credit_purchase,

            'block_purchase_on_credit_limit' =>
                $this->block_purchase_on_credit_limit,

            'default_purchase_discount_percent' =>
                $this->default_purchase_discount_percent,

            'is_tax_applicable' =>
                $this->is_tax_applicable,

            'default_tax_percent' =>
                $this->default_tax_percent,

            'is_withholding_tax_applicable' =>
                $this->is_withholding_tax_applicable,

            'withholding_tax_percent' =>
                $this->withholding_tax_percent,

            'purchase_price_basis' =>
                $this->purchase_price_basis,

            'default_purchase_order_term' =>
                $this->default_purchase_order_term,

            'payment_instruction' =>
                $this->payment_instruction,

            'notes' => $this->notes,
            'is_active' => $this->is_active,

            'supplier' => $this->whenLoaded(
                'supplier',
                fn (): array => [
                    'id' => $this->supplier->id,
                    'company_id' =>
                        $this->supplier->company_id,
                    'branch_id' =>
                        $this->supplier->branch_id,
                    'name' => $this->supplier->name,
                    'code' => $this->supplier->code,
                    'business_name' =>
                        $this->supplier->business_name,
                    'is_active' =>
                        $this->supplier->is_active,
                ]
            ),

            'creator' => $this->whenLoaded(
                'creator',
                fn (): array => [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                    'email' => $this->creator->email,
                ]
            ),

            'updater' => $this->whenLoaded(
                'updater',
                fn (): array => [
                    'id' => $this->updater->id,
                    'name' => $this->updater->name,
                    'email' => $this->updater->email,
                ]
            ),

            'created_at' =>
                $this->created_at?->toISOString(),

            'updated_at' =>
                $this->updated_at?->toISOString(),
        ];
    }
}