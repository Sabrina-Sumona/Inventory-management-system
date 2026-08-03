<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

class CustomerPolicy
{
    public function viewAny(
        User $user
    ): bool {
        return $user->hasPermission(
            'customer.view'
        );
    }

    public function view(
        User $user,
        Customer $customer
    ): bool {
        return $user->hasPermission(
            'customer.view'
        ) && $this->canAccessCustomer(
            $user,
            $customer
        );
    }

    public function create(
        User $user
    ): bool {
        return $user->hasPermission(
            'customer.create'
        );
    }

    public function update(
        User $user,
        Customer $customer
    ): bool {
        return $user->hasPermission(
            'customer.update'
        ) && $this->canAccessCustomer(
            $user,
            $customer
        );
    }

    public function delete(
        User $user,
        Customer $customer
    ): bool {
        return $user->hasPermission(
            'customer.delete'
        ) && $this->canAccessCustomer(
            $user,
            $customer
        );
    }

    public function restore(
        User $user,
        Customer $customer
    ): bool {
        return $user->hasPermission(
            'customer.delete'
        ) && $this->canAccessCustomer(
            $user,
            $customer
        );
    }

    public function forceDelete(
        User $user,
        Customer $customer
    ): bool {
        return false;
    }

    private function canAccessCustomer(
        User $user,
        Customer $customer
    ): bool {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if (
            ! $user->canAccessCompany(
                $customer->company_id
            )
        ) {
            return false;
        }

        if ($customer->branch_id === null) {
            return true;
        }

        return $user->canAccessBranch(
            $customer->branch_id
        );
    }
}