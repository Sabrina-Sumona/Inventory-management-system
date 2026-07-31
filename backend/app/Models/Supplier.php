<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'branch_id',
        'name',
        'code',
        'business_name',
        'email',
        'phone',
        'alternate_phone',
        'website',
        'tax_identification_number',
        'trade_license_number',
        'address_line_1',
        'address_line_2',
        'city',
        'district',
        'postal_code',
        'country',
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
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
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
            'suppliers.is_active',
            true
        );
    }

    public function scopeForCompany(
        Builder $query,
        int $companyId
    ): Builder {
        return $query->where(
            'suppliers.company_id',
            $companyId
        );
    }

    public function scopeForBranch(
        Builder $query,
        int $branchId
    ): Builder {
        return $query->where(
            'suppliers.branch_id',
            $branchId
        );
    }

    public function scopeAccessibleTo(
        Builder $query,
        User $user
    ): Builder {
        if ($user->isSuperAdmin()) {
            return $query;
        }

        $query->where(
            'suppliers.company_id',
            $user->company_id
        );

        $accessibleBranchIds = $user
            ->branches()
            ->pluck('branches.id');

        return $query->where(
            function (Builder $branchQuery) use (
                $accessibleBranchIds
            ): void {
                $branchQuery
                    ->whereNull(
                        'suppliers.branch_id'
                    )
                    ->orWhereIn(
                        'suppliers.branch_id',
                        $accessibleBranchIds
                    );
            }
        );
    }
}