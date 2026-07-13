<?php

namespace Tests\Support\Tenant;

use App\Models\Department;
use App\Models\Tenant;
use Illuminate\Support\Str;

abstract class DepartmentManagementTestCase extends UserManagementTestCase
{
    /**
     * Register and authenticate a temporary tenant.
     *
     * @return array{
     *     registration: array<string, mixed>,
     *     tenant: Tenant,
     *     headers: array<string, string>
     * }
     */
    protected function authenticatedDepartmentContext(
        string $prefix = 'department'
    ): array {
        $registration = $this->registerTestTenant($prefix);

        $token = $this->loginRegisteredTenant(
            $registration
        );

        return [
            'registration' => $registration,
            'tenant' => $registration['tenant'],
            'headers' => $this->tenantHeaders(
                $token,
                $registration['domain']
            ),
        ];
    }

    /**
     * Create a department directly inside a tenant database.
     */
    protected function createDepartment(
        Tenant $tenant,
        array $attributes = []
    ): Department {
        return $this->runInTenant(
            $tenant,
            function () use ($attributes): Department {
                $defaults = [
                    'name' => sprintf(
                        'Department %s',
                        strtoupper(Str::random(8))
                    ),
                    'code' => strtoupper(Str::random(8)),
                    'description' => null,
                    'sort_order' => 0,
                    'is_active' => true,
                    'created_by' => null,
                    'updated_by' => null,
                ];

                return Department::query()
                    ->create(
                        array_merge(
                            $defaults,
                            $attributes
                        )
                    )
                    ->fresh();
            }
        );
    }

    /**
     * Find a department inside a tenant database.
     */
    protected function findDepartmentByUuid(
        Tenant $tenant,
        string $uuid,
        bool $withTrashed = false
    ): ?Department {
        return $this->runInTenant(
            $tenant,
            static function () use (
                $uuid,
                $withTrashed
            ): ?Department {
                $query = Department::query();

                if ($withTrashed) {
                    $query->withTrashed();
                }

                return $query
                    ->where('uuid', $uuid)
                    ->first();
            }
        );
    }
}