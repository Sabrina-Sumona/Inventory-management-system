<?php

namespace App\Policies;

use App\Models\CustomerContact;
use App\Models\User;

class CustomerContactPolicy
{
    public function viewAny(
        User $user
    ): bool {
        return $user->hasPermission(
            'customer-contact.view'
        );
    }

    public function view(
        User $user,
        CustomerContact $customerContact
    ): bool {
        return $user->hasPermission(
            'customer-contact.view'
        ) && $this->canAccessCustomerContact(
            $user,
            $customerContact
        );
    }

    public function create(
        User $user
    ): bool {
        return $user->hasPermission(
            'customer-contact.create'
        );
    }

    public function update(
        User $user,
        CustomerContact $customerContact
    ): bool {
        return $user->hasPermission(
            'customer-contact.update'
        ) && $this->canAccessCustomerContact(
            $user,
            $customerContact
        );
    }

    public function delete(
        User $user,
        CustomerContact $customerContact
    ): bool {
        return $user->hasPermission(
            'customer-contact.delete'
        ) && $this->canAccessCustomerContact(
            $user,
            $customerContact
        );
    }

    public function restore(
        User $user,
        CustomerContact $customerContact
    ): bool {
        return $user->hasPermission(
            'customer-contact.delete'
        ) && $this->canAccessCustomerContact(
            $user,
            $customerContact
        );
    }

    public function forceDelete(
        User $user,
        CustomerContact $customerContact
    ): bool {
        return false;
    }

    private function canAccessCustomerContact(
        User $user,
        CustomerContact $customerContact
    ): bool {
        $customerContact->loadMissing(
            'customer'
        );

        if ($customerContact->customer === null) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        if (
            ! $user->canAccessCompany(
                $customerContact
                    ->customer
                    ->company_id
            )
        ) {
            return false;
        }

        if (
            $customerContact
                ->customer
                ->branch_id === null
        ) {
            return true;
        }

        return $user->canAccessBranch(
            $customerContact
                ->customer
                ->branch_id
        );
    }
}