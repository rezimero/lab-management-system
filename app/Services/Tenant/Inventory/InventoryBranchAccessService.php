<?php

namespace App\Services\Tenant\Inventory;

use App\Models\Branch;
use App\Models\Tenant\User as TenantUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class InventoryBranchAccessService
{
    public function scopeVisible(
        Builder $query,
        TenantUser $user,
        string $column = 'branch_id'
    ): Builder {
        if ($this->isAdministrator($user)) {
            return $query;
        }

        return $query->whereIn($column, $this->assignedBranchIds($user));
    }

    /**
     * @return array<int, int>
     */
    public function assignedBranchIds(TenantUser $user): array
    {
        $user->loadMissing('branches');

        return $user->branches
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();
    }

    public function authorize(
        TenantUser $user,
        Branch $branch,
        bool $forMutation = false,
        bool $allowAssignedCollectionParent = false
    ): void {
        if ($forMutation && ! $branch->is_active) {
            throw ValidationException::withMessages([
                'branch_uuid' => ['Inventory cannot be posted to an inactive branch.'],
            ]);
        }

        if ($this->isAdministrator($user)) {
            return;
        }

        $assignedIds = $this->assignedBranchIds($user);

        if (in_array((int) $branch->getKey(), $assignedIds, true)) {
            return;
        }

        if ($allowAssignedCollectionParent) {
            $canUseParent = $user->branches->contains(
                static fn (Branch $assigned): bool => (
                    $assigned->type === 'collection_centre'
                    && (int) $assigned->parent_branch_id === (int) $branch->getKey()
                )
            );

            if ($canUseParent) {
                return;
            }
        }

        throw ValidationException::withMessages([
            'branch_uuid' => ['The selected branch is outside your assigned branch network.'],
        ]);
    }

    public function isAdministrator(TenantUser $user): bool
    {
        return in_array($user->role, ['admin', 'superadmin'], true);
    }
}
