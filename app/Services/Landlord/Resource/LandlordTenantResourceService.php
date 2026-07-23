<?php

namespace App\Services\Landlord\Resource;

use App\Models\Tenant;
use App\Services\Landlord\TenantContextRunner;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class LandlordTenantResourceService
{
    public function __construct(
        protected TenantContextRunner $contextRunner,
    ) {
    }

    /**
     * List and paginate one tenant model across every tenant.
     *
     * @param class-string<Model> $modelClass
     * @param array<int, string> $searchFields
     * @return array<string, mixed>
     */
    public function paginate(
        string $modelClass,
        string $responseKey,
        string $resourceLabel,
        array $searchFields,
        mixed $search,
        mixed $perPage,
        mixed $page,
    ): array {
        $records = [];
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            try {
                $tenantRecords = $this->contextRunner->run(
                    $tenant,
                    function () use (
                        $modelClass,
                        $tenant,
                    ): array {
                        return $modelClass::query()
                            ->get()
                            ->map(
                                fn (Model $record): array =>
                                    $this->present(
                                        $record,
                                        $tenant,
                                    )
                            )
                            ->all();
                    }
                );

                array_push($records, ...$tenantRecords);
            } catch (\Exception) {
                Log::error(
                    'Error fetching '
                    .$resourceLabel
                    .' from tenant: '
                    .$tenant->name
                );
            }
        }

        if ($search !== null && $search !== '') {
            $records = array_filter(
                $records,
                fn (array $record): bool =>
                    $this->matchesSearch(
                        $record,
                        $searchFields,
                        (string) $search,
                    )
            );
        }

        usort(
            $records,
            static fn (array $left, array $right): int =>
                strtotime($right['created_at'])
                - strtotime($left['created_at'])
        );

        $perPage = (int) $perPage;
        $page = (int) $page;
        $total = count($records);
        $offset = ($page - 1) * $perPage;
        $data = array_slice(
            $records,
            $offset,
            $perPage,
        );

        return [
            $responseKey => [
                'data' => $data,
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => ceil(
                    $total / $perPage
                ),
            ],
            'total_tenants' => $tenants->count(),
        ];
    }

    /**
     * Find a resource by its numeric identifier across all tenants.
     *
     * @param class-string<Model> $modelClass
     * @return array<string, mixed>|null
     */
    public function find(
        string $modelClass,
        mixed $id,
    ): ?array {
        foreach (Tenant::all() as $tenant) {
            try {
                $record = $this->contextRunner->run(
                    $tenant,
                    function () use (
                        $modelClass,
                        $id,
                        $tenant,
                    ): ?array {
                        $model = $modelClass::query()
                            ->where('id', $id)
                            ->first();

                        return $model instanceof Model
                            ? $this->present(
                                $model,
                                $tenant,
                            )
                            : null;
                    }
                );

                if ($record !== null) {
                    return $record;
                }
            } catch (\Exception) {
                Log::error(
                    'Error searching tenant: '
                    .$tenant->name
                );
            }
        }

        return null;
    }

    /**
     * Delete a resource by its numeric identifier across all tenants.
     *
     * @param class-string<Model> $modelClass
     * @return array{id: mixed, name: mixed, domain: mixed}|null
     */
    public function delete(
        string $modelClass,
        mixed $id,
    ): ?array {
        foreach (Tenant::all() as $tenant) {
            try {
                $deleted = $this->contextRunner->run(
                    $tenant,
                    function () use (
                        $modelClass,
                        $id,
                    ): bool {
                        $model = $modelClass::query()
                            ->where('id', $id)
                            ->first();

                        if (! $model instanceof Model) {
                            return false;
                        }

                        $model->delete();

                        return true;
                    }
                );

                if ($deleted) {
                    return [
                        'id' => $tenant->id,
                        'name' => $tenant->name,
                        'domain' => $tenant->domain,
                    ];
                }
            } catch (\Exception) {
                Log::error(
                    'Error searching tenant: '
                    .$tenant->name
                );
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function present(
        Model $record,
        Tenant $tenant,
    ): array {
        return array_merge(
            $record->toArray(),
            [
                'tenant_name' => $tenant->name,
                'tenant_domain' => $tenant->domain,
            ]
        );
    }

    /**
     * @param array<string, mixed> $record
     * @param array<int, string> $fields
     */
    private function matchesSearch(
        array $record,
        array $fields,
        string $search,
    ): bool {
        foreach ($fields as $field) {
            if (
                stripos(
                    (string) ($record[$field] ?? ''),
                    $search,
                ) !== false
            ) {
                return true;
            }
        }

        return false;
    }
}
