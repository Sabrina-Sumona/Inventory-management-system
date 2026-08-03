<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\CustomerFinancialSetting;
use App\Models\User;

class CustomerFinancialSettingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(
            'customer-financial-setting.view'
        );
    }

    public function view(
        User $user,
        CustomerFinancialSetting $customerFinancialSetting
    ): bool {
        return $user->hasPermission(
            'customer-financial-setting.view'
        ) && $this->canAccessCustomer(
            $user,
            $customerFinancialSetting->customer
        );
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(
            'customer-financial-setting.create'
        );
    }

    public function update(
        User $user,
        CustomerFinancialSetting $customerFinancialSetting
    ): bool {
        return $user->hasPermission(
            'customer-financial-setting.update'
        ) && $this->canAccessCustomer(
            $user,
            $customerFinancialSetting->customer
        );
    }

    public function delete(
        User $user,
        CustomerFinancialSetting $customerFinancialSetting
    ): bool {
        return $user->hasPermission(
            'customer-financial-setting.delete'
        ) && $this->canAccessCustomer(
            $user,
            $customerFinancialSetting->customer
        );
    }

    public function restore(
        User $user,
        CustomerFinancialSetting $customerFinancialSetting
    ): bool {
        return false;
    }

    public function forceDelete(
        User $user,
        CustomerFinancialSetting $customerFinancialSetting
    ): bool {
        return false;
    }

    private function canAccessCustomer(
        User $user,
        ?Customer $customer
    ): bool {
        if ($customer === null) {
            return false;
        }

        return Customer::query()
            ->accessibleTo($user)
            ->whereKey($customer->id)
            ->exists();
    }
}