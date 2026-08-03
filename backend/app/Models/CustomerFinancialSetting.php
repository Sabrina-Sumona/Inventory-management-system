<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerFinancialSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'currency_code',
        'default_payment_method',
        'payment_term_days',
        'credit_limit',
        'allow_credit_sale',
        'block_sale_on_credit_limit',
        'default_sales_discount_percent',
        'is_tax_applicable',
        'default_tax_percent',
        'is_withholding_tax_applicable',
        'withholding_tax_percent',
        'sales_price_basis',
        'default_sales_order_term',
        'payment_instruction',
        'notes',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'payment_term_days' => 'integer',
            'credit_limit' => 'decimal:2',
            'allow_credit_sale' => 'boolean',
            'block_sale_on_credit_limit' => 'boolean',
            'default_sales_discount_percent' => 'decimal:2',
            'is_tax_applicable' => 'boolean',
            'default_tax_percent' => 'decimal:2',
            'is_withholding_tax_applicable' => 'boolean',
            'withholding_tax_percent' => 'decimal:2',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(
            Customer::class
        );
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'updated_by'
        );
    }

    public function scopeActive(
        Builder $query
    ): Builder {
        return $query->where(
            'customer_financial_settings.is_active',
            true
        );
    }

    public function scopeForCustomer(
        Builder $query,
        int $customerId
    ): Builder {
        return $query->where(
            'customer_financial_settings.customer_id',
            $customerId
        );
    }

    public function scopeAccessibleTo(
        Builder $query,
        User $user
    ): Builder {
        return $query->whereHas(
            'customer',
            fn (Builder $customerQuery) =>
                $customerQuery->accessibleTo(
                    $user
                )
        );
    }
}