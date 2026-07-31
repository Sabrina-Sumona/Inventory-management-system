<?php

namespace App\Policies;

use App\Models\Supplier;
use App\Models\User;

class SupplierPolicy
{
    public function before(
        User $user,
        string $ability
    ): ?bool {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasPermission(
            'supplier.view'
        );
    }

    public function view(
        User $user,
        Supplier $supplier
    ): bool {
        return $user->hasPermission(
            'supplier.view'
        ) && $this->canAccessSupplier(
            $user,
            $supplier
        );
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(
            'supplier.create'
        );
    }

    public function update(
        User $user,
        Supplier $supplier
    ): bool {
        return $user->hasPermission(
            'supplier.update'
        ) && $this->canAccessSupplier(
            $user,
            $supplier
        );
    }

    public function delete(
        User $user,
        Supplier $supplier
    ): bool {
        return $user->hasPermission(
            'supplier.delete'
        ) && $this->canAccessSupplier(
            $user,
            $supplier
        );
    }

    public function restore(
        User $user,
        Supplier $supplier
    ): bool {
        return $user->hasPermission(
            'supplier.delete'
        ) && $this->canAccessSupplier(
            $user,
            $supplier
        );
    }

    public function forceDelete(
        User $user,
        Supplier $supplier
    ): bool {
        return false;
    }

    private function canAccessSupplier(
        User $user,
        Supplier $supplier
    ): bool {
        if (
            ! $user->canAccessCompany(
                $supplier->company_id
            )
        ) {
            return false;
        }

        if ($supplier->branch_id === null) {
            return true;
        }

        return $user->canAccessBranch(
            $supplier->branch_id
        );
    }
}