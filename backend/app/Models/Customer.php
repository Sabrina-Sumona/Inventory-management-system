<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'branch_id',
        'name',
        'code',
        'business_name',
        'customer_type',
        'email',
        'phone',
        'alternate_phone',
        'website',
        'tax_identification_number',
        'trade_license_number',
        'billing_address_line_1',
        'billing_address_line_2',
        'billing_city',
        'billing_district',
        'billing_postal_code',
        'billing_country',
        'shipping_address_line_1',
        'shipping_address_line_2',
        'shipping_city',
        'shipping_district',
        'shipping_postal_code',
        'shipping_country',
        'payment_term_days',
        'credit_limit',
        'opening_balance',
        'opening_balance_type',
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
            'opening_balance' => 'decimal:2',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(
            Company::class
        );
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(
            Branch::class
        );
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(
            CustomerContact::class
        );
    }

    public function financialSetting(): HasOne
    {
        return $this->hasOne(
            CustomerFinancialSetting::class
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
            'customers.is_active',
            true
        );
    }

    public function scopeForCompany(
        Builder $query,
        int $companyId
    ): Builder {
        return $query->where(
            'customers.company_id',
            $companyId
        );
    }

    public function scopeForBranch(
        Builder $query,
        int $branchId
    ): Builder {
        return $query->where(
            'customers.branch_id',
            $branchId
        );
    }

    public function scopeForType(
        Builder $query,
        string $customerType
    ): Builder {
        return $query->where(
            'customers.customer_type',
            $customerType
        );
    }

    public function scopeAccessibleTo(
        Builder $query,
        User $user
    ): Builder {
        if ($user->isSuperAdmin()) {
            return $query;
        }

        if ($user->company_id === null) {
            return $query->whereRaw(
                '1 = 0'
            );
        }

        $query->where(
            'customers.company_id',
            $user->company_id
        );

        $accessibleBranchIds = $user
            ->branches()
            ->pluck('branches.id');

        return $query->where(
            function (
                Builder $branchQuery
            ) use (
                $accessibleBranchIds
            ): void {
                $branchQuery
                    ->whereNull(
                        'customers.branch_id'
                    )
                    ->orWhereIn(
                        'customers.branch_id',
                        $accessibleBranchIds
                    );
            }
        );
    }
}