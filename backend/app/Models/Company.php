<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'email',
        'website',
        'phone',
        'address',
        'timezone',
        'currency',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function branches(): HasMany
    {
        return $this->hasMany(
            Branch::class
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

    public function roles(): HasMany
    {
        return $this->hasMany(
            Role::class
        );
    }

    public function users(): HasMany
    {
        return $this->hasMany(
            User::class
        );
    }

    public function scopeAccessibleTo(
        Builder $query,
        User $user
    ): Builder {
        if ($user->isSuperAdmin()) {
            return $query;
        }

        return $query->whereKey(
            $user->company_id
        );
    }
}