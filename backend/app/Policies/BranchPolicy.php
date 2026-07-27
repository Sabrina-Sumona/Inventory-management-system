<?php

namespace App\Policies;

use App\Models\Branch;
use App\Models\User;

class BranchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('branch.view');
    }

    public function view(User $user, Branch $branch): bool
    {
        return $user->hasPermission('branch.view')
            && $user->canAccessBranch($branch);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('branch.create')
            && $user->company_id !== null;
    }

    public function update(User $user, Branch $branch): bool
    {
        return $user->hasPermission('branch.update')
            && $user->canAccessBranch($branch);
    }

    public function delete(User $user, Branch $branch): bool
    {
        return $user->hasPermission('branch.delete')
            && $user->canAccessBranch($branch);
    }

    public function restore(User $user, Branch $branch): bool
    {
        return $user->hasPermission('branch.update')
            && $user->canAccessCompany($branch->company_id);
    }

    public function forceDelete(User $user, Branch $branch): bool
    {
        return false;
    }
}