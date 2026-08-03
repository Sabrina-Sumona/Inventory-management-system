<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerFinancialSettingResource extends JsonResource
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

            'customer' =>
                $this->whenLoaded(
                    'customer',
                    fn (): array => [
                        'id' =>
                            $this->customer->id,

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

                        'company' =>
                            $this->customer
                                ->relationLoaded(
                                    'company'
                                )
                                ? [
                                    'id' =>
                                        $this->customer
                                            ->company?->id,

                                    'name' =>
                                        $this->customer
                                            ->company?->name,

                                    'code' =>
                                        $this->customer
                                            ->company?->code,
                                ]
                                : null,

                        'branch' =>
                            $this->customer
                                ->relationLoaded(
                                    'branch'
                                )
                                ? [
                                    'id' =>
                                        $this->customer
                                            ->branch?->id,

                                    'name' =>
                                        $this->customer
                                            ->branch?->name,

                                    'code' =>
                                        $this->customer
                                            ->branch?->code,
                                ]
                                : null,
                    ]
                ),

            'currency_code' =>
                $this->currency_code,

            'default_payment_method' =>
                $this->default_payment_method,

            'payment_term_days' =>
                $this->payment_term_days,

            'credit_limit' =>
                $this->credit_limit,

            'allow_credit_sale' =>
                $this->allow_credit_sale,

            'block_sale_on_credit_limit' =>
                $this->block_sale_on_credit_limit,

            'default_sales_discount_percent' =>
                $this->default_sales_discount_percent,

            'is_tax_applicable' =>
                $this->is_tax_applicable,

            'default_tax_percent' =>
                $this->default_tax_percent,

            'is_withholding_tax_applicable' =>
                $this->is_withholding_tax_applicable,

            'withholding_tax_percent' =>
                $this->withholding_tax_percent,

            'sales_price_basis' =>
                $this->sales_price_basis,

            'default_sales_order_term' =>
                $this->default_sales_order_term,

            'payment_instruction' =>
                $this->payment_instruction,

            'notes' =>
                $this->notes,

            'is_active' =>
                $this->is_active,

            'created_by' =>
                $this->created_by,

            'updated_by' =>
                $this->updated_by,

            'creator' =>
                $this->whenLoaded(
                    'creator',
                    fn (): ?array =>
                        $this->creator === null
                            ? null
                            : [
                                'id' =>
                                    $this->creator->id,

                                'name' =>
                                    $this->creator->name,

                                'email' =>
                                    $this->creator->email,
                            ]
                ),

            'updater' =>
                $this->whenLoaded(
                    'updater',
                    fn (): ?array =>
                        $this->updater === null
                            ? null
                            : [
                                'id' =>
                                    $this->updater->id,

                                'name' =>
                                    $this->updater->name,

                                'email' =>
                                    $this->updater->email,
                            ]
                ),

            'created_at' =>
                $this->created_at?->toISOString(),

            'updated_at' =>
                $this->updated_at?->toISOString(),
        ];
    }
}