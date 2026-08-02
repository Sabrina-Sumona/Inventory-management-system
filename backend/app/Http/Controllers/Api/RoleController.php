<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class RoleController extends Controller
{
    public function index(
        Request $request
    ): JsonResponse {
        Gate::authorize(
            'create',
            User::class
        );

        /** @var User $authenticatedUser */
        $authenticatedUser = $request->user();

        $validated = $request->validate([
            'company_id' => [
                'nullable',
                'integer',
                'exists:companies,id',
            ],
        ]);

        $companyId = $this->resolveCompanyId(
            $authenticatedUser,
            $validated['company_id'] ?? null
        );

        $roles = Role::query()
            ->select([
                'id',
                'company_id',
                'name',
                'code',
                'description',
                'is_system',
                'is_active',
            ])
            ->where('is_active', true)
            ->when(
                $companyId === null,
                fn ($query) => $query
                    ->whereNull('company_id')
                    ->where('is_system', true),
                fn ($query) => $query->where(
                    'company_id',
                    $companyId
                )
            )
            ->orderBy('name')
            ->get()
            ->map(
                fn (Role $role): array => [
                    'id' => $role->id,
                    'company_id' =>
                        $role->company_id,
                    'name' => $role->name,
                    'code' => $role->code,
                    'description' =>
                        $role->description,
                    'is_system' =>
                        $role->is_system,
                    'is_active' =>
                        $role->is_active,
                ]
            )
            ->values();

        return response()->json([
            'success' => true,
            'message' =>
                'Assignable roles retrieved successfully.',
            'data' => [
                'roles' => $roles,
            ],
        ]);
    }

    private function resolveCompanyId(
        User $authenticatedUser,
        mixed $requestedCompanyId
    ): ?int {
        if (
            ! $authenticatedUser->isSuperAdmin()
        ) {
            return $authenticatedUser->company_id !== null
                ? (int) $authenticatedUser->company_id
                : null;
        }

        return $requestedCompanyId !== null
            ? (int) $requestedCompanyId
            : null;
    }
}