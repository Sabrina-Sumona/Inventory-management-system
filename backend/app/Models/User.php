<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use InvalidArgumentException;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'name',
        'email',
        'password',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            'role_user'
        )
            ->withPivot('assigned_by')
            ->withTimestamps();
    }

    public function hasRole(string $roleCode): bool
    {
        return $this->roles()
            ->where('roles.code', $roleCode)
            ->where('roles.is_active', true)
            ->exists();
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('SUPER-ADMIN');
    }

    public function canAccessCompany(Company|int $company): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $companyId = $company instanceof Company
            ? $company->getKey()
            : $company;

        return $this->company_id !== null
            && (int) $this->company_id === (int) $companyId;
    }

    public function assignRole(
        Role $role,
        ?User $assignedBy = null
    ): void {
        if (! $role->is_active) {
            throw new InvalidArgumentException(
                'An inactive role cannot be assigned.'
            );
        }

        if ($role->company_id === null) {
            if (
                $this->company_id !== null
                || ! $role->is_system
            ) {
                throw new InvalidArgumentException(
                    'This global role cannot be assigned to the user.'
                );
            }
        } elseif (
            $this->company_id === null
            || (int) $role->company_id !== (int) $this->company_id
        ) {
            throw new InvalidArgumentException(
                'The role belongs to a different company.'
            );
        }

        $this->roles()->syncWithoutDetaching([
            $role->id => [
                'assigned_by' => $assignedBy?->id,
            ],
        ]);
    }
}