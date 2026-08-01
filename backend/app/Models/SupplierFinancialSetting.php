<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierFinancialSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'currency_code',
        'default_payment_method',
        'payment_term_days',
        'credit_limit',
        'allow_credit_purchase',
        'block_purchase_on_credit_limit',
        'default_purchase_discount_percent',
        'is_tax_applicable',
        'default_tax_percent',
        'is_withholding_tax_applicable',
        'withholding_tax_percent',
        'purchase_price_basis',
        'default_purchase_order_term',
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

            'allow_credit_purchase' => 'boolean',
            'block_purchase_on_credit_limit' => 'boolean',

            'default_purchase_discount_percent' =>
                'decimal:2',

            'is_tax_applicable' => 'boolean',
            'default_tax_percent' => 'decimal:2',

            'is_withholding_tax_applicable' =>
                'boolean',

            'withholding_tax_percent' =>
                'decimal:2',

            'is_active' => 'boolean',

            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(
            Supplier::class
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
            'supplier_financial_settings.is_active',
            true
        );
    }

    public function scopeForSupplier(
        Builder $query,
        int $supplierId
    ): Builder {
        return $query->where(
            'supplier_financial_settings.supplier_id',
            $supplierId
        );
    }

    public function scopeAccessibleTo(
        Builder $query,
        User $user
    ): Builder {
        if ($user->isSuperAdmin()) {
            return $query;
        }

        return $query->whereHas(
            'supplier',
            fn (Builder $supplierQuery) =>
                $supplierQuery->accessibleTo(
                    $user
                )
        );
    }
}