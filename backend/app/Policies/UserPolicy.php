<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function before(
        User $authenticatedUser,
        string $ability
    ): ?bool {
        if ($authenticatedUser->isSuperAdmin()) {
            return true;
        }

        return null;
    }

    public function viewAny(
        User $authenticatedUser
    ): bool {
        return $authenticatedUser->hasPermission(
            'user.view'
        );
    }

    public function view(
        User $authenticatedUser,
        User $targetUser
    ): bool {
        return $authenticatedUser->hasPermission(
            'user.view'
        ) && $this->belongsToSameCompany(
            $authenticatedUser,
            $targetUser
        );
    }

    public function create(
        User $authenticatedUser
    ): bool {
        return $authenticatedUser->hasPermission(
            'user.create'
        );
    }

    public function update(
        User $authenticatedUser,
        User $targetUser
    ): bool {
        return $authenticatedUser->hasPermission(
            'user.update'
        ) && $this->belongsToSameCompany(
            $authenticatedUser,
            $targetUser
        );
    }

    public function manageAssignments(
        User $authenticatedUser,
        User $targetUser
    ): bool {
        return $authenticatedUser->hasPermission(
            'user.update'
        ) && $this->belongsToSameCompany(
            $authenticatedUser,
            $targetUser
        );
    }

    public function deactivate(
        User $authenticatedUser,
        User $targetUser
    ): bool {
        return $authenticatedUser->hasPermission(
            'user.deactivate'
        ) && $this->belongsToSameCompany(
            $authenticatedUser,
            $targetUser
        );
    }

    private function belongsToSameCompany(
        User $authenticatedUser,
        User $targetUser
    ): bool {
        return $authenticatedUser->company_id !== null
            && $targetUser->company_id !== null
            && (int) $authenticatedUser->company_id
                === (int) $targetUser->company_id;
    }
}