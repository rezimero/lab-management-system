<?php

namespace App\Services\Tenant\Department;

use App\Models\Department;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DepartmentQueryService
{
    /**
     * Return a paginated department listing.
     */
    public function paginate(
        ?string $search = null,
        bool $activeOnly = false,
        int $perPage = 15
    ): LengthAwarePaginator {
        return Department::query()
            ->when(
                $search,
                function ($query) use ($search): void {
                    $query->where(
                        function ($query) use ($search): void {
                            $query
                                ->where(
                                    'name',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'code',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'description',
                                    'like',
                                    "%{$search}%"
                                );
                        }
                    );
                }
            )
            ->when(
                $activeOnly,
                fn ($query) => $query->active()
            )
            ->ordered()
            ->paginate($perPage);
    }

    /**
     * Find a department using its public UUID.
     */
    public function find(string $uuid): Department
    {
        return Department::query()
            ->where('uuid', $uuid)
            ->firstOrFail();
    }
}