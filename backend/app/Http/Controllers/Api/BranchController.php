<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Branch\StoreBranchRequest;
use App\Http\Requests\Branch\UpdateBranchRequest;
use App\Http\Resources\BranchResource;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class BranchController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Branch::class);

        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'search' => [
                'nullable',
                'string',
                'max:100',
            ],
            'is_active' => [
                'nullable',
                'boolean',
            ],
            'is_head_office' => [
                'nullable',
                'boolean',
            ],
            'sort_by' => [
                'nullable',
                'in:name,code,city,district,created_at,updated_at',
            ],
            'sort_direction' => [
                'nullable',
                'in:asc,desc',
            ],
            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
            'page' => [
                'nullable',
                'integer',
                'min:1',
            ],
        ]);

        $search = $validated['search'] ?? null;
        $sortBy = $validated['sort_by'] ?? 'name';
        $sortDirection =
            $validated['sort_direction'] ?? 'asc';
        $perPage = $validated['per_page'] ?? 15;

        $branches = Branch::query()
            ->accessibleTo($user)
            ->with('company')
            ->withCount([
                'warehouses',
                'users',
            ])
            ->when(
                $search,
                function ($query, string $search): void {
                    $normalizedSearch = '%'
                        . Str::lower($search)
                        . '%';

                    $query->where(
                        function ($searchQuery) use (
                            $normalizedSearch
                        ): void {
                            $searchQuery
                                ->whereRaw(
                                    'LOWER(branches.name) LIKE ?',
                                    [$normalizedSearch]
                                )
                                ->orWhereRaw(
                                    'LOWER(branches.code) LIKE ?',
                                    [$normalizedSearch]
                                )
                                ->orWhereRaw(
                                    'LOWER(branches.email) LIKE ?',
                                    [$normalizedSearch]
                                )
                                ->orWhereRaw(
                                    'LOWER(branches.phone) LIKE ?',
                                    [$normalizedSearch]
                                )
                                ->orWhereRaw(
                                    'LOWER(branches.city) LIKE ?',
                                    [$normalizedSearch]
                                )
                                ->orWhereRaw(
                                    'LOWER(branches.district) LIKE ?',
                                    [$normalizedSearch]
                                );
                        }
                    );
                }
)
            ->when(
                array_key_exists(
                    'is_active',
                    $validated
                ),
                fn ($query) => $query->where(
                    'branches.is_active',
                    $validated['is_active']
                )
            )
            ->when(
                array_key_exists(
                    'is_head_office',
                    $validated
                ),
                fn ($query) => $query->where(
                    'branches.is_head_office',
                    $validated['is_head_office']
                )
            )
            ->orderBy(
                "branches.{$sortBy}",
                $sortDirection
            )
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'success' => true,
            'message' => 'Branches retrieved successfully.',
            'data' => [
                'branches' => BranchResource::collection(
                    $branches->getCollection()
                )->resolve($request),

                'pagination' => [
                    'current_page' => $branches->currentPage(),
                    'last_page' => $branches->lastPage(),
                    'per_page' => $branches->perPage(),
                    'total' => $branches->total(),
                    'from' => $branches->firstItem(),
                    'to' => $branches->lastItem(),
                ],
            ],
        ]);
    }

    public function store(
        StoreBranchRequest $request
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();

        $branch = DB::transaction(
            function () use ($request, $user): Branch {
                $validated = $request->validated();

                $isHeadOffice = (bool) (
                    $validated['is_head_office']
                    ?? false
                );

                if ($isHeadOffice) {
                    Branch::query()
                        ->where(
                            'company_id',
                            $user->company_id
                        )
                        ->update([
                            'is_head_office' => false,
                        ]);
                }

                $branch = Branch::create([
                    ...$validated,
                    'company_id' => $user->company_id,
                    'is_head_office' => $isHeadOffice,
                    'is_active' => $validated[
                        'is_active'
                    ] ?? true,
                ]);

                /*
                 * Ensure the branch creator can access
                 * the newly created branch.
                 */
                $user->assignBranch(
                    branch: $branch,
                    isPrimary: false,
                    assignedBy: $user,
                );

                return $branch;
            }
        );

        $branch->load('company')->loadCount([
            'warehouses',
            'users',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Branch created successfully.',
            'data' => [
                'branch' => (
                    new BranchResource($branch)
                )->resolve($request),
            ],
        ], 201);
    }

    public function show(
        Request $request,
        Branch $branch
    ): JsonResponse {
        Gate::authorize('view', $branch);

        $branch->load('company')->loadCount([
            'warehouses',
            'users',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Branch retrieved successfully.',
            'data' => [
                'branch' => (
                    new BranchResource($branch)
                )->resolve($request),
            ],
        ]);
    }

    public function update(
        UpdateBranchRequest $request,
        Branch $branch
    ): JsonResponse {
        DB::transaction(
            function () use ($request, $branch): void {
                $validated = $request->validated();

                if (
                    array_key_exists(
                        'is_head_office',
                        $validated
                    )
                    && $validated['is_head_office']
                ) {
                    Branch::query()
                        ->where(
                            'company_id',
                            $branch->company_id
                        )
                        ->whereKeyNot($branch->id)
                        ->update([
                            'is_head_office' => false,
                        ]);
                }

                $branch->update($validated);
            }
        );

        $branch->refresh()
            ->load('company')
            ->loadCount([
                'warehouses',
                'users',
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Branch updated successfully.',
            'data' => [
                'branch' => (
                    new BranchResource($branch)
                )->resolve($request),
            ],
        ]);
    }

    public function destroy(
        Request $request,
        Branch $branch
    ): JsonResponse {
        Gate::authorize('delete', $branch);

        if ($branch->is_head_office) {
            throw ValidationException::withMessages([
                'branch' => [
                    'The head-office branch cannot be deleted.',
                ],
            ]);
        }

        if ($branch->warehouses()->exists()) {
            throw ValidationException::withMessages([
                'branch' => [
                    'A branch containing warehouses cannot be deleted.',
                ],
            ]);
        }

        $branch->delete();

        return response()->json([
            'success' => true,
            'message' => 'Branch deleted successfully.',
            'data' => null,
        ]);
    }

    public function restore(
        Request $request,
        int $branch
    ): JsonResponse {
        $branchModel = Branch::onlyTrashed()
            ->findOrFail($branch);

        Gate::authorize('restore', $branchModel);

        $branchModel->restore();

        $branchModel->load('company')->loadCount([
            'warehouses',
            'users',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Branch restored successfully.',
            'data' => [
                'branch' => (
                    new BranchResource($branchModel)
                )->resolve($request),
            ],
        ]);
    }
}