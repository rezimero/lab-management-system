<?php

namespace App\Services\Tenant\Referral;

use App\Models\Branch;
use App\Models\Tenant\User as TenantUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class ReferralBranchAccessService
{
    public function scopeVisible(
        Builder $query,
        TenantUser $user
    ): Builder {
        if ($this->isAdministrator($user)) {
            return $query;
        }

        $assignedIds = $this->assignedBranchIds($user);

        return $query->whereHas(
            'orderItem.order',
            static fn (Builder $order): Builder => $order->where(
                static fn (Builder $branches): Builder => $branches
                    ->whereIn('collection_branch_id', $assignedIds)
                    ->orWhereIn('processing_branch_id', $assignedIds)
            )
        );
    }

    public function scopeVisibleOrderItems(
        Builder $query,
        TenantUser $user
    ): Builder {
        if ($this->isAdministrator($user)) {
            return $query;
        }

        $assignedIds = $this->assignedBranchIds($user);

        return $query->whereHas(
            'order',
            static fn (Builder $order): Builder => $order->where(
                static fn (Builder $branches): Builder => $branches
                    ->whereIn('collection_branch_id', $assignedIds)
                    ->orWhereIn('processing_branch_id', $assignedIds)
            )
        );
    }

    public function authorizeMutation(
        TenantUser $user,
        Branch $processingBranch
    ): void {
        if (! $processingBranch->is_active) {
            throw ValidationException::withMessages([
                'order_item_uuid' => [
                    'The order processing branch is inactive.',
                ],
            ]);
        }

        if ($this->isAdministrator($user)) {
            return;
        }

        if (in_array(
            (int) $processingBranch->getKey(),
            $this->assignedBranchIds($user),
            true
        )) {
            return;
        }

        throw ValidationException::withMessages([
            'order_item_uuid' => [
                'The order processing branch is outside your assigned branches.',
            ],
        ]);
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

    public function isAdministrator(TenantUser $user): bool
    {
        return in_array(
            $user->role,
            ['admin', 'superadmin'],
            true
        );
    }
}
