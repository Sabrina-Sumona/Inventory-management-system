<?php

namespace App\Http\Requests\User;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SyncBranchAssignmentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var User|null $authenticatedUser */
        $authenticatedUser = $this->user();

        /** @var User|null $targetUser */
        $targetUser = $this->route('user');

        return $authenticatedUser !== null
            && $targetUser !== null
            && $authenticatedUser->can(
                'manageAssignments',
                $targetUser
            );
    }

    public function rules(): array
    {
        /** @var User|null $targetUser */
        $targetUser = $this->route('user');

        return [
            'branch_ids' => [
                'required',
                'array',
            ],

            'branch_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('branches', 'id')
                    ->where(
                        fn ($query) => $query
                            ->whereNull('deleted_at')
                            ->when(
                                $targetUser?->company_id !== null,
                                fn ($branchQuery) => $branchQuery
                                    ->where(
                                        'company_id',
                                        $targetUser->company_id
                                    )
                            )
                    ),
            ],

            'primary_branch_id' => [
                'nullable',
                'integer',
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $branchIds = array_map(
                    'intval',
                    $this->input('branch_ids', [])
                );

                $primaryBranchId =
                    $this->input('primary_branch_id');

                if (
                    $primaryBranchId !== null
                    && ! in_array(
                        (int) $primaryBranchId,
                        $branchIds,
                        true
                    )
                ) {
                    $validator->errors()->add(
                        'primary_branch_id',
                        'The primary branch must be included in branch_ids.'
                    );
                }

                /** @var User|null $targetUser */
                $targetUser = $this->route('user');

                if (
                    $targetUser?->company_id === null
                    && $branchIds !== []
                ) {
                    $validator->errors()->add(
                        'branch_ids',
                        'Global users do not require branch assignments.'
                    );
                }

                if (
                    $targetUser?->company_id !== null
                    && $branchIds !== []
                ) {
                    $validCount = Branch::query()
                        ->where(
                            'company_id',
                            $targetUser->company_id
                        )
                        ->whereIn('id', $branchIds)
                        ->whereNull('deleted_at')
                        ->count();

                    if ($validCount !== count($branchIds)) {
                        $validator->errors()->add(
                            'branch_ids',
                            'One or more branches are invalid for this user.'
                        );
                    }
                }
            },
        ];
    }
}