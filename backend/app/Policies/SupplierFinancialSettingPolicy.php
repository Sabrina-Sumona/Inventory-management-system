<?php

namespace App\Policies;

use App\Models\SupplierFinancialSetting;
use App\Models\User;

class SupplierFinancialSettingPolicy
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
            'supplier-financial-setting.view'
        );
    }

    public function view(
        User $user,
        SupplierFinancialSetting $supplierFinancialSetting
    ): bool {
        return $user->hasPermission(
            'supplier-financial-setting.view'
        ) && $this->canAccessSetting(
            $user,
            $supplierFinancialSetting
        );
    }

    public function create(
        User $user
    ): bool {
        return $user->hasPermission(
            'supplier-financial-setting.create'
        );
    }

    public function update(
        User $user,
        SupplierFinancialSetting $supplierFinancialSetting
    ): bool {
        return $user->hasPermission(
            'supplier-financial-setting.update'
        ) && $this->canAccessSetting(
            $user,
            $supplierFinancialSetting
        );
    }

    public function delete(
        User $user,
        SupplierFinancialSetting $supplierFinancialSetting
    ): bool {
        return $user->hasPermission(
            'supplier-financial-setting.delete'
        ) && $this->canAccessSetting(
            $user,
            $supplierFinancialSetting
        );
    }

    public function restore(
        User $user,
        SupplierFinancialSetting $supplierFinancialSetting
    ): bool {
        return false;
    }

    public function forceDelete(
        User $user,
        SupplierFinancialSetting $supplierFinancialSetting
    ): bool {
        return false;
    }

    private function canAccessSetting(
        User $user,
        SupplierFinancialSetting $supplierFinancialSetting
    ): bool {
        $supplier = $supplierFinancialSetting
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