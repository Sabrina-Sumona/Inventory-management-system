<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'email',
        'phone',
        'address',
        'city',
        'district',
        'postal_code',
        'is_head_office',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_head_office' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(
            Company::class
        );
    }

    public function warehouses(): HasMany
    {
        return $this->hasMany(
            Warehouse::class
        );
    }

    public function suppliers(): HasMany
    {
        return $this->hasMany(
            Supplier::class
        );
    }

    public function customers(): HasMany
    {
        return $this->hasMany(
            Customer::class
        );
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'branch_user'
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
            return $query->whereRaw(
                '1 = 0'
            );
        }

        return $query
            ->where(
                'branches.company_id',
                $user->company_id
            )
            ->whereHas(
                'users',
                fn (Builder $userQuery) =>
                    $userQuery->where(
                        'users.id',
                        $user->id
                    )
            );
    }
}