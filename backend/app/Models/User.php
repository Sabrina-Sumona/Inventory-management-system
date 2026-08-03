<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'name',
        'email',
        'password',
        'is_active',
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
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(
            Company::class
        );
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

    public function createdSuppliers(): HasMany
    {
        return $this->hasMany(
            Supplier::class,
            'created_by'
        );
    }

    public function updatedSuppliers(): HasMany
    {
        return $this->hasMany(
            Supplier::class,
            'updated_by'
        );
    }

    public function createdSupplierContacts(): HasMany
    {
        return $this->hasMany(
            SupplierContact::class,
            'created_by'
        );
    }

    public function updatedSupplierContacts(): HasMany
    {
        return $this->hasMany(
            SupplierContact::class,
            'updated_by'
        );
    }

    public function createdSupplierFinancialSettings(): HasMany
    {
        return $this->hasMany(
            SupplierFinancialSetting::class,
            'created_by'
        );
    }

    public function updatedSupplierFinancialSettings(): HasMany
    {
        return $this->hasMany(
            SupplierFinancialSetting::class,
            'updated_by'
        );
    }

    public function hasRole(
        string $roleCode
    ): bool {
        return $this->roles()
            ->where(
                'roles.code',
                $roleCode
            )
            ->where(
                'roles.is_active',
                true
            )
            ->exists();
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(
            'SUPER-ADMIN'
        );
    }

    public function canAccessCompany(
        Company|int $company
    ): bool {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $companyId =
            $company instanceof Company
                ? $company->getKey()
                : $company;

        return $this->company_id !== null
            && (int) $this->company_id ===
                (int) $companyId;
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
            || (int) $role->company_id !==
                (int) $this->company_id
        ) {
            throw new InvalidArgumentException(
                'The role belongs to a different company.'
            );
        }

        $this->roles()
            ->syncWithoutDetaching([
                $role->id => [
                    'assigned_by' =>
                        $assignedBy?->id,
                ],
            ]);
    }

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(
            Branch::class,
            'branch_user'
        )
            ->withPivot([
                'assigned_by',
                'is_primary',
            ])
            ->withTimestamps();
    }

    public function canAccessBranch(
        Branch|int $branch
    ): bool {
        $branchModel =
            $branch instanceof Branch
                ? $branch
                : Branch::find($branch);

        if ($branchModel === null) {
            return false;
        }

        if ($this->isSuperAdmin()) {
            return true;
        }

        if (
            ! $this->canAccessCompany(
                $branchModel->company_id
            )
        ) {
            return false;
        }

        return $this->branches()
            ->where(
                'branches.id',
                $branchModel->id
            )
            ->exists();
    }

    public function assignBranch(
        Branch $branch,
        bool $isPrimary = false,
        ?User $assignedBy = null
    ): void {
        if ($this->company_id === null) {
            throw new InvalidArgumentException(
                'A global user does not require branch assignment.'
            );
        }

        if (
            (int) $branch->company_id !==
            (int) $this->company_id
        ) {
            throw new InvalidArgumentException(
                'The branch belongs to a different company.'
            );
        }

        DB::transaction(
            function () use (
                $branch,
                $isPrimary,
                $assignedBy
            ): void {
                if ($isPrimary) {
                    DB::table(
                        'branch_user'
                    )
                        ->where(
                            'user_id',
                            $this->id
                        )
                        ->update([
                            'is_primary' => false,
                            'updated_at' => now(),
                        ]);
                }

                $this->branches()
                    ->syncWithoutDetaching([
                        $branch->id => [
                            'assigned_by' =>
                                $assignedBy?->id,
                            'is_primary' =>
                                $isPrimary,
                        ],
                    ]);
            }
        );
    }

    public function removeBranch(
        Branch $branch
    ): void {
        $this->branches()
            ->detach($branch->id);
    }

    public function primaryBranch(): ?Branch
    {
        return $this->branches()
            ->wherePivot(
                'is_primary',
                true
            )
            ->first();
    }

    public function warehouses(): BelongsToMany
    {
        return $this->belongsToMany(
            Warehouse::class,
            'user_warehouse'
        )
            ->withPivot([
                'assigned_by',
                'is_primary',
            ])
            ->withTimestamps();
    }

    public function canAccessWarehouse(
        Warehouse|int $warehouse
    ): bool {
        $warehouseModel =
            $warehouse instanceof Warehouse
                ? $warehouse
                : Warehouse::find(
                    $warehouse
                );

        if ($warehouseModel === null) {
            return false;
        }

        if ($this->isSuperAdmin()) {
            return true;
        }

        if (
            ! $this->canAccessCompany(
                $warehouseModel->company_id
            )
        ) {
            return false;
        }

        if (
            ! $this->canAccessBranch(
                $warehouseModel->branch_id
            )
        ) {
            return false;
        }

        return $this->warehouses()
            ->where(
                'warehouses.id',
                $warehouseModel->id
            )
            ->exists();
    }

    public function assignWarehouse(
        Warehouse $warehouse,
        bool $isPrimary = false,
        ?User $assignedBy = null
    ): void {
        if ($this->company_id === null) {
            throw new InvalidArgumentException(
                'A global user does not require warehouse assignment.'
            );
        }

        if (
            (int) $warehouse->company_id !==
            (int) $this->company_id
        ) {
            throw new InvalidArgumentException(
                'The warehouse belongs to a different company.'
            );
        }

        if (
            ! $this->canAccessBranch(
                $warehouse->branch_id
            )
        ) {
            throw new InvalidArgumentException(
                'The user must be assigned to the warehouse branch first.'
            );
        }

        DB::transaction(
            function () use (
                $warehouse,
                $isPrimary,
                $assignedBy
            ): void {
                if ($isPrimary) {
                    DB::table(
                        'user_warehouse'
                    )
                        ->where(
                            'user_id',
                            $this->id
                        )
                        ->update([
                            'is_primary' => false,
                            'updated_at' => now(),
                        ]);
                }

                $this->warehouses()
                    ->syncWithoutDetaching([
                        $warehouse->id => [
                            'assigned_by' =>
                                $assignedBy?->id,
                            'is_primary' =>
                                $isPrimary,
                        ],
                    ]);
            }
        );
    }

    public function removeWarehouse(
        Warehouse $warehouse
    ): void {
        $this->warehouses()
            ->detach($warehouse->id);
    }

    public function primaryWarehouse(): ?Warehouse
    {
        return $this->warehouses()
            ->wherePivot(
                'is_primary',
                true
            )
            ->first();
    }

    public function hasPermission(
        string $permissionCode
    ): bool {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->roles()
            ->where(
                'roles.is_active',
                true
            )
            ->whereHas(
                'permissions',
                fn (Builder $query) =>
                    $query
                        ->where(
                            'permissions.code',
                            $permissionCode
                        )
                        ->where(
                            'permissions.is_active',
                            true
                        )
            )
            ->exists();
    }
}