<?php

namespace App\Services\Tenant\User;

use App\Models\Tenant;
use App\Models\Tenant\User as TenantUser;

class TenantUserQueryService
{
    /**
     * Return tenant users using the existing API pagination format.
     *
     * @return array{
     *     data: array<int, array<string, mixed>>,
     *     total: int,
     *     per_page: int,
     *     current_page: int,
     *     last_page: int
     * }
     */
    public function paginate(
        Tenant $tenant,
        ?string $search,
        int $perPage,
        int $page
    ): array {
        $perPage = max(1, $perPage);
        $page = max(1, $page);

        $query = TenantUser::query();

        if (filled($search)) {
            $query->where(function ($query) use ($search): void {
                $query
                    ->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%');
            });
        }

        $total = $query->count();

        $users = $query
            ->orderByDesc('created_at')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get()
            ->map(function (TenantUser $user) use ($tenant): array {
                return array_merge(
                    $user->toArray(),
                    [
                        'tenant_name' => $tenant->name,
                        'tenant_domain' => $tenant->domain,
                    ]
                );
            })
            ->values()
            ->all();

        return [
            'data' => $users,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => (int) ceil($total / $perPage),
        ];
    }

    public function find(int|string $id): ?TenantUser
    {
        return TenantUser::query()
            ->whereKey($id)
            ->first();
    }
}