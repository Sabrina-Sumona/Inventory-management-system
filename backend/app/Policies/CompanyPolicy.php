<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;

class CompanyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('company.view');
    }

    public function view(User $user, Company $company): bool
    {
        return $user->hasPermission('company.view')
            && $user->canAccessCompany($company);
    }

    public function create(User $user): bool
    {
        /*
         * Company creation is currently reserved for Super Admin.
         * Gate::before handles the Super Admin override.
         */
        return false;
    }

    public function update(User $user, Company $company): bool
    {
        return $user->hasPermission('company.update')
            && $user->canAccessCompany($company);
    }

    public function delete(User $user, Company $company): bool
    {
        /*
         * Company deletion is not currently enabled.
         */
        return false;
    }

    public function restore(User $user, Company $company): bool
    {
        return false;
    }

    public function forceDelete(User $user, Company $company): bool
    {
        return false;
    }
}