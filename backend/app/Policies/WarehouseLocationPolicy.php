<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WarehouseLocation;

class WarehouseLocationPolicy
{
    public function viewAny(
        User $user
    ): bool {
        return $user->hasPermission(
            'warehouse-location.view'
        );
    }

    public function view(
        User $user,
        WarehouseLocation $warehouseLocation
    ): bool {
        return $user->hasPermission(
            'warehouse-location.view'
        ) && $user->canAccessWarehouse(
            $warehouseLocation->warehouse_id
        );
    }

    public function create(
        User $user
    ): bool {
        return $user->hasPermission(
            'warehouse-location.create'
        ) && $user->company_id !== null;
    }

    public function update(
        User $user,
        WarehouseLocation $warehouseLocation
    ): bool {
        return $user->hasPermission(
            'warehouse-location.update'
        ) && $user->canAccessWarehouse(
            $warehouseLocation->warehouse_id
        );
    }

    public function delete(
        User $user,
        WarehouseLocation $warehouseLocation
    ): bool {
        return $user->hasPermission(
            'warehouse-location.delete'
        ) && $user->canAccessWarehouse(
            $warehouseLocation->warehouse_id
        );
    }

    public function restore(
        User $user,
        WarehouseLocation $warehouseLocation
    ): bool {
        return $user->hasPermission(
            'warehouse-location.update'
        ) && $user->canAccessWarehouse(
            $warehouseLocation->warehouse_id
        );
    }

    public function forceDelete(
        User $user,
        WarehouseLocation $warehouseLocation
    ): bool {
        return false;
    }
}