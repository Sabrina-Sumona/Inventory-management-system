<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\UpdateCompanyRequest;
use App\Http\Resources\CompanyResource;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CompanyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Company::class);

        /** @var User $user */
        $user = $request->user();

        $companies = Company::query()
            ->accessibleTo($user)
            ->withCount([
                'branches',
                'warehouses',
                'users',
            ])
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Companies retrieved successfully.',
            'data' => [
                'companies' => CompanyResource::collection(
                    $companies
                )->resolve($request),
            ],
        ]);
    }

    public function show(
        Request $request,
        Company $company
    ): JsonResponse {
        Gate::authorize('view', $company);

        $company->loadCount([
            'branches',
            'warehouses',
            'users',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Company retrieved successfully.',
            'data' => [
                'company' => (
                    new CompanyResource($company)
                )->resolve($request),
            ],
        ]);
    }

    public function update(
        UpdateCompanyRequest $request,
        Company $company
    ): JsonResponse {
        $validated = $request->validated();

        /*
         * Only a global Super Admin may activate or deactivate
         * a company. Company administrators may update normal
         * company information but cannot disable their company.
         */
        if (! $request->user()->isSuperAdmin()) {
            unset($validated['is_active']);
        }

        $company->update($validated);

        $company->refresh()->loadCount([
            'branches',
            'warehouses',
            'users',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Company updated successfully.',
            'data' => [
                'company' => (
                    new CompanyResource($company)
                )->resolve($request),
            ],
        ]);
    }
}