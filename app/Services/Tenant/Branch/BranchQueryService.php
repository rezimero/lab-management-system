<?php

namespace App\Services\Tenant\Branch;

use App\Models\Branch;
use App\Models\Tenant\User as TenantUser;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BranchQueryService
{
    public function paginate(
        TenantUser $user,
        ?string $search,
        ?string $type,
        ?bool $active,
        int $perPage
    ): LengthAwarePaginator {
        return Branch::query()
            ->with(['parent'])
            ->withCount(['users', 'collectionOrders', 'processingOrders'])
            ->when(
                ! $user->isAdmin(),
                fn ($query) => $query->whereHas(
                    'users',
                    fn ($query) => $query->where(
                        'users.id',
                        $user->getKey()
                    )
                )
            )
            ->when(
                $search,
                fn ($query, string $value) => $query->where(
                    function ($query) use ($value): void {
                        $query->where('name', 'like', "%{$value}%")
                            ->orWhere('code', 'like', "%{$value}%")
                            ->orWhere('city', 'like', "%{$value}%")
                            ->orWhere('state', 'like', "%{$value}%");
                    }
                )
            )
            ->when($type, fn ($query, string $value) => $query->where('type', $value))
            ->when(
                $active !== null,
                fn ($query) => $query->where('is_active', $active)
            )
            ->orderByDesc('is_head_office')
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function find(TenantUser $user, string $uuid): Branch
    {
        return Branch::query()
            ->with(['parent', 'users'])
            ->withCount(['collectionOrders', 'processingOrders'])
            ->when(
                ! $user->isAdmin(),
                fn ($query) => $query->whereHas(
                    'users',
                    fn ($query) => $query->where(
                        'users.id',
                        $user->getKey()
                    )
                )
            )
            ->where('uuid', $uuid)
            ->firstOrFail();
    }
}
