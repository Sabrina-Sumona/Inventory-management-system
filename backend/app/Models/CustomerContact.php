<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerContact extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'customer_id',
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
            'customer_contacts.is_active',
            true
        );
    }

    public function scopePrimary(
        Builder $query
    ): Builder {
        return $query->where(
            'customer_contacts.is_primary',
            true
        );
    }

    public function scopeForCustomer(
        Builder $query,
        int $customerId
    ): Builder {
        return $query->where(
            'customer_contacts.customer_id',
            $customerId
        );
    }

    public function scopeForType(
        Builder $query,
        string $contactType
    ): Builder {
        return $query->where(
            'customer_contacts.contact_type',
            $contactType
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