<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WarehouseLocation extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'branch_id',
        'warehouse_id',
        'parent_id',
        'name',
        'code',
        'type',
        'barcode',
        'capacity',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'decimal:3',
            'is_active' => 'boolean',
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

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(
            Warehouse::class
        );
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(
            WarehouseLocation::class,
            'parent_id'
        );
    }

    public function children(): HasMany
    {
        return $this->hasMany(
            WarehouseLocation::class,
            'parent_id'
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
            return $query->whereRaw('1 = 0');
        }

        return $query
            ->where(
                'warehouse_locations.company_id',
                $user->company_id
            )
            ->whereHas(
                'warehouse',
                function (
                    Builder $warehouseQuery
                ) use ($user): void {
                    $warehouseQuery
                        ->accessibleTo($user);
                }
            );
    }
}