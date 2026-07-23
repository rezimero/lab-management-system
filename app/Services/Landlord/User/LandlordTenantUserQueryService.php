<?php

namespace App\Services\Landlord\User;

use App\Models\Tenant;
use App\Models\Tenant\User as TenantUser;
use App\Services\Landlord\TenantContextRunner;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Throwable;

class LandlordTenantUserQueryService
{
    public function __construct(
        protected TenantContextRunner $contextRunner,
        protected LandlordTenantUserPresenter $presenter
    ) {
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array{
     *     users: array{
     *         data: array<int, array<string, mixed>>,
     *         total: int,
     *         per_page: int,
     *         current_page: int,
     *         last_page: int
     *     },
     *     total_tenants: int
     * }
     */
    public function paginate(array $filters): array
    {
        $search = trim(
            (string) ($filters['search'] ?? '')
        );

        $perPage = (int) (
            $filters['per_page'] ?? 25
        );

        $page = (int) (
            $filters['page'] ?? 1
        );

        $tenantQuery = Tenant::query()
            ->orderBy('name');

        if (isset($filters['tenant_id'])) {
            $tenantQuery->whereKey(
                $filters['tenant_id']
            );
        }

        $tenants = $tenantQuery->get();
        $allUsers = [];

        foreach ($tenants as $tenant) {
            try {
                $tenantUsers = $this->contextRunner->run(
                    $tenant,
                    fn () => $this->tenantUsers($search)
                );

                foreach ($tenantUsers as $tenantUser) {
                    $allUsers[] = $this->presenter->present(
                        $tenant,
                        $tenantUser
                    );
                }
            } catch (Throwable $exception) {
                Log::warning(
                    'Unable to inspect tenant users.',
                    [
                        'tenant_id' => $tenant->getKey(),
                        'tenant_domain' => $tenant->domain,
                        'exception' =>
                            $exception->getMessage(),
                    ]
                );
            }
        }

        if ($search !== '') {
            $allUsers = $this->filterPresentedUsers(
                $allUsers,
                $search
            );
        }

        usort(
            $allUsers,
            static fn (
                array $left,
                array $right
            ): int => strcmp(
                (string) ($right['created_at'] ?? ''),
                (string) ($left['created_at'] ?? '')
            )
        );

        $total = count($allUsers);
        $offset = ($page - 1) * $perPage;

        return [
            'users' => [
                'data' => array_slice(
                    $allUsers,
                    $offset,
                    $perPage
                ),
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => max(
                    1,
                    (int) ceil($total / $perPage)
                ),
            ],
            'total_tenants' => $tenants->count(),
        ];
    }

    public function findInCurrentTenant(
        int|string $id
    ): ?TenantUser {
        return TenantUser::query()
            ->whereKey($id)
            ->first();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, TenantUser>
     */
    private function tenantUsers(string $search)
    {
        $query = TenantUser::query();

        if ($search !== '') {
            $query->where(
                function (Builder $query) use ($search): void {
                    $query
                        ->where(
                            'name',
                            'like',
                            '%'.$search.'%'
                        )
                        ->orWhere(
                            'email',
                            'like',
                            '%'.$search.'%'
                        )
                        ->orWhere(
                            'role',
                            'like',
                            '%'.$search.'%'
                        );
                }
            );
        }

        return $query
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * @param array<int, array<string, mixed>> $users
     *
     * @return array<int, array<string, mixed>>
     */
    private function filterPresentedUsers(
        array $users,
        string $search
    ): array {
        $normalisedSearch = mb_strtolower($search);

        return array_values(
            array_filter(
                $users,
                static function (
                    array $user
                ) use ($normalisedSearch): bool {
                    $haystack = mb_strtolower(
                        implode(
                            ' ',
                            [
                                $user['name'],
                                $user['email'],
                                $user['role'],
                                $user['tenant_name'],
                                $user['tenant_domain'],
                            ]
                        )
                    );

                    return str_contains(
                        $haystack,
                        $normalisedSearch
                    );
                }
            )
        );
    }
}
