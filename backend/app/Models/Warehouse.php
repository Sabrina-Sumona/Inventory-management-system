<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Warehouse extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'branch_id',
        'name',
        'code',
        'email',
        'phone',
        'address',
        'city',
        'district',
        'postal_code',
        'is_primary',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'is_active' => 'boolean',
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

    public function locations(): HasMany
    {
        return $this->hasMany(WarehouseLocation::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'user_warehouse'
        )
            ->withPivot([
                'assigned_by',
                'is_primary',
            ])
            ->withTimestamps();
    }

    public function scopeAccessibleTo(
        Builder $query,
        User $user
    ): Builder {
        if ($user->isSuperAdmin()) {
            return $query;
        }

        if ($user->company_id === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query
            ->where('warehouses.company_id', $user->company_id)
            ->whereHas(
                'branch.users',
                fn (Builder $branchUserQuery) => $branchUserQuery
                    ->where('users.id', $user->id)
            )
            ->whereHas(
                'users',
                fn (Builder $warehouseUserQuery) => $warehouseUserQuery
                    ->where('users.id', $user->id)
            );
    }

}