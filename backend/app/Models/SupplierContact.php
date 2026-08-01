<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupplierContact extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'supplier_id',
        'name',
        'designation',
        'department',
        'contact_type',
        'email',
        'phone',
        'alternate_phone',
        'is_primary',
        'is_active',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
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
            'supplier_contacts.is_active',
            true
        );
    }

    public function scopePrimary(
        Builder $query
    ): Builder {
        return $query->where(
            'supplier_contacts.is_primary',
            true
        );
    }

    public function scopeForSupplier(
        Builder $query,
        int $supplierId
    ): Builder {
        return $query->where(
            'supplier_contacts.supplier_id',
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