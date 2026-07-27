<?php

namespace App\Http\Resources\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CurrentUserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $activeRoles = $this->roles
            ->where('is_active', true)
            ->values();

        $activePermissions = $activeRoles
            ->flatMap(
                fn ($role) => $role->permissions
                    ->where('is_active', true)
            )
            ->unique('id')
            ->values();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at,

            'company' => $this->company
                ? [
                    'id' => $this->company->id,
                    'name' => $this->company->name,
                    'code' => $this->company->code,
                ]
                : null,

            'roles' => $activeRoles
                ->map(fn ($role) => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'code' => $role->code,
                ])
                ->all(),

            'permissions' => $activePermissions
                ->map(fn ($permission) => [
                    'name' => $permission->name,
                    'code' => $permission->code,
                    'module' => $permission->module,
                    'action' => $permission->action,
                ])
                ->all(),

            'branches' => $this->branches
                ->map(fn ($branch) => [
                    'id' => $branch->id,
                    'name' => $branch->name,
                    'code' => $branch->code,
                    'is_primary' => (bool) $branch->pivot?->is_primary,
                ])
                ->values()
                ->all(),

            'warehouses' => $this->warehouses
                ->map(fn ($warehouse) => [
                    'id' => $warehouse->id,
                    'name' => $warehouse->name,
                    'code' => $warehouse->code,
                    'branch_id' => $warehouse->branch_id,
                    'is_primary' => (bool) $warehouse->pivot?->is_primary,
                ])
                ->values()
                ->all(),
        ];
    }
}