<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Warehouse;

class WarehousePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('warehouse.view');
    }

    public function view(User $user, Warehouse $warehouse): bool
    {
        return $user->hasPermission('warehouse.view')
            && $user->canAccessWarehouse($warehouse);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('warehouse.create')
            && $user->company_id !== null;
    }

    public function update(User $user, Warehouse $warehouse): bool
    {
        return $user->hasPermission('warehouse.update')
            && $user->canAccessWarehouse($warehouse);
    }

    public function delete(User $user, Warehouse $warehouse): bool
    {
        return $user->hasPermission('warehouse.delete')
            && $user->canAccessWarehouse($warehouse);
    }

    public function restore(User $user, Warehouse $warehouse): bool
    {
        return $user->hasPermission('warehouse.update')
            && $user->canAccessCompany($warehouse->company_id)
            && $user->canAccessBranch($warehouse->branch_id);
    }

    public function forceDelete(User $user, Warehouse $warehouse): bool
    {
        return false;
    }
}