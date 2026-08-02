<?php

namespace App\Services\Tenant\Branch;

use App\Models\Branch;
use App\Models\Tenant\User as TenantUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BranchManagementService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function save(
        ?Branch $branch,
        array $data,
        TenantUser $actor
    ): Branch {
        return DB::connection('tenant')->transaction(
            function () use ($branch, $data, $actor): Branch {
                $branch ??= new Branch;

                $parent = $this->resolveParent(
                    $data['type'],
                    $data['parent_branch_uuid'] ?? null,
                    $branch
                );

                if (
                    $branch->exists
                    && $branch->is_head_office
                    && array_key_exists('is_head_office', $data)
                    && ! (bool) $data['is_head_office']
                ) {
                    throw ValidationException::withMessages([
                        'is_head_office' => [
                            'The current head office cannot be demoted directly.',
                        ],
                    ]);
                }

                if (
                    $branch->exists
                    && $branch->is_active
                    && array_key_exists('is_active', $data)
                    && ! (bool) $data['is_active']
                ) {
                    throw ValidationException::withMessages([
                        'is_active' => [
                            'Use the branch deactivation action so operational dependencies can be checked.',
                        ],
                    ]);
                }

                if ((bool) ($data['is_head_office'] ?? false)) {
                    Branch::query()
                        ->lockForUpdate()
                        ->get();

                    Branch::query()
                        ->when(
                            $branch->exists,
                            fn ($query) => $query->whereKeyNot(
                                $branch->getKey()
                            )
                        )
                        ->update(['is_head_office' => false]);
                }

                $branch->fill([
                    'code' => strtoupper(trim($data['code'])),
                    'name' => trim($data['name']),
                    'type' => $data['type'],
                    'parent_branch_id' => $parent?->getKey(),
                    'email' => $data['email'] ?? null,
                    'phone' => $data['phone'] ?? null,
                    'address_line_1' => $data['address_line_1'] ?? null,
                    'address_line_2' => $data['address_line_2'] ?? null,
                    'city' => $data['city'] ?? null,
                    'state' => $data['state'] ?? null,
                    'country' => $data['country'] ?? 'Nigeria',
                    'is_head_office' => $data['is_head_office'] ?? false,
                    'is_active' => $data['is_active'] ?? true,
                    'updated_by' => $actor->getKey(),
                ]);

                if (! $branch->exists) {
                    $branch->created_by = $actor->getKey();
                }

                $branch->save();

                if ($branch->is_head_office) {
                    DB::connection('tenant')
                        ->table('branch_user')
                        ->where('user_id', $actor->getKey())
                        ->update(['is_primary' => false]);

                    $actor->branches()->syncWithoutDetaching([
                        $branch->getKey() => [
                            'is_primary' => true,
                            'assigned_by' => $actor->getKey(),
                        ],
                    ]);
                }

                return $branch->fresh(['parent', 'users']);
            }
        );
    }

    /**
     * @param  array<int, int>  $userIds
     */
    public function assignUsers(
        Branch $branch,
        array $userIds,
        ?int $primaryUserId,
        TenantUser $actor
    ): Branch {
        return DB::connection('tenant')->transaction(
            function () use (
                $branch,
                $userIds,
                $primaryUserId,
                $actor
            ): Branch {
                $users = TenantUser::query()
                    ->whereIn('id', $userIds)
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->get();

                if ($users->count() !== count(array_unique($userIds))) {
                    throw ValidationException::withMessages([
                        'user_ids' => [
                            'One or more selected users are unavailable or inactive.',
                        ],
                    ]);
                }

                $sync = [];

                foreach ($users as $user) {
                    $sync[$user->getKey()] = [
                        'is_primary' => (int) $user->getKey() === $primaryUserId,
                        'assigned_by' => $actor->getKey(),
                    ];
                }

                $branch->users()->sync($sync);

                if ($primaryUserId !== null) {
                    DB::connection('tenant')
                        ->table('branch_user')
                        ->where('user_id', $primaryUserId)
                        ->where('branch_id', '!=', $branch->getKey())
                        ->update(['is_primary' => false]);
                }

                return $branch->fresh('users');
            }
        );
    }

    public function deactivate(Branch $branch, TenantUser $actor): Branch
    {
        return DB::connection('tenant')->transaction(
            function () use ($branch, $actor): Branch {
                $lockedBranch = Branch::query()
                    ->whereKey($branch->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($lockedBranch->is_head_office) {
                    throw ValidationException::withMessages([
                        'branch' => [
                            'The head-office branch cannot be deactivated.',
                        ],
                    ]);
                }

                if (! $lockedBranch->is_active) {
                    throw ValidationException::withMessages([
                        'branch' => ['The branch is already inactive.'],
                    ]);
                }

                if ($lockedBranch->children()->where('is_active', true)->exists()) {
                    throw ValidationException::withMessages([
                        'branch' => [
                            'Re-route or deactivate active collection centres before deactivating this laboratory.',
                        ],
                    ]);
                }

                $hasOpenOrders = $lockedBranch->collectionOrders()
                    ->whereNotIn('status', ['completed', 'cancelled'])
                    ->exists()
                    || $lockedBranch->processingOrders()
                        ->whereNotIn('status', ['completed', 'cancelled'])
                        ->exists();

                if ($hasOpenOrders) {
                    throw ValidationException::withMessages([
                        'branch' => [
                            'Complete or cancel open laboratory orders before deactivating this branch.',
                        ],
                    ]);
                }

                $lockedBranch->forceFill([
                    'is_active' => false,
                    'updated_by' => $actor->getKey(),
                ])->save();

                return $lockedBranch->fresh();
            }
        );
    }

    private function resolveParent(
        string $type,
        ?string $parentUuid,
        Branch $branch
    ): ?Branch {
        if ($type === 'laboratory') {
            return null;
        }

        if ($parentUuid === null || $parentUuid === '') {
            throw ValidationException::withMessages([
                'parent_branch_uuid' => [
                    'A collection centre must route specimens to an active laboratory branch.',
                ],
            ]);
        }

        $parent = Branch::query()
            ->where('uuid', $parentUuid)
            ->where('type', 'laboratory')
            ->where('is_active', true)
            ->first();

        if ($parent === null || $parent->is($branch)) {
            throw ValidationException::withMessages([
                'parent_branch_uuid' => [
                    'The selected processing laboratory is unavailable.',
                ],
            ]);
        }

        return $parent;
    }
}
