<?php

namespace App\Policies;

use App\Models\SupplierContact;
use App\Models\User;

class SupplierContactPolicy
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

    public function viewAny(
        User $user
    ): bool {
        return $user->hasPermission(
            'supplier-contact.view'
        );
    }

    public function view(
        User $user,
        SupplierContact $supplierContact
    ): bool {
        return $user->hasPermission(
            'supplier-contact.view'
        ) && $this->canAccessContact(
            $user,
            $supplierContact
        );
    }

    public function create(
        User $user
    ): bool {
        return $user->hasPermission(
            'supplier-contact.create'
        );
    }

    public function update(
        User $user,
        SupplierContact $supplierContact
    ): bool {
        return $user->hasPermission(
            'supplier-contact.update'
        ) && $this->canAccessContact(
            $user,
            $supplierContact
        );
    }

    public function delete(
        User $user,
        SupplierContact $supplierContact
    ): bool {
        return $user->hasPermission(
            'supplier-contact.delete'
        ) && $this->canAccessContact(
            $user,
            $supplierContact
        );
    }

    public function restore(
        User $user,
        SupplierContact $supplierContact
    ): bool {
        return $user->hasPermission(
            'supplier-contact.delete'
        ) && $this->canAccessContact(
            $user,
            $supplierContact
        );
    }

    public function forceDelete(
        User $user,
        SupplierContact $supplierContact
    ): bool {
        return false;
    }

    private function canAccessContact(
        User $user,
        SupplierContact $supplierContact
    ): bool {
        $supplier = $supplierContact
            ->supplier()
            ->first();

        if ($supplier === null) {
            return false;
        }

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