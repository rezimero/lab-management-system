<?php

namespace App\Services;

use App\Models\Department;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DepartmentService
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
            ->when($search, function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when(
                $activeOnly,
                fn ($query) => $query->active()
            )
            ->ordered()
            ->paginate($perPage);
    }

    /**
     * Return all active departments.
     */
    public function allActive(): Collection
    {
        return Department::query()
            ->active()
            ->ordered()
            ->get();
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

    /**
     * Create a department in the current tenant database.
     */
    public function create(array $data): Department
    {
        $this->validateUniqueness($data);

        return DB::connection('tenant')->transaction(
            function () use ($data): Department {
                $userId = Auth::id();

                $department = Department::query()->create([
                    'name' => trim($data['name']),
                    'code' => $this->normalizeCode($data['code']),
                    'description' => $this->normalizeDescription(
                        $data['description'] ?? null
                    ),
                    'sort_order' => $data['sort_order'] ?? 0,
                    'is_active' => $data['is_active'] ?? true,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);

                return $department->fresh();
            }
        );
    }

    /**
     * Update a department in the current tenant database.
     */
    public function update(
        Department $department,
        array $data
    ): Department {
        $this->validateUniqueness($data, $department);

        return DB::connection('tenant')->transaction(
            function () use ($department, $data): Department {
                $payload = array_intersect_key(
                    $data,
                    array_flip([
                        'name',
                        'code',
                        'description',
                        'sort_order',
                        'is_active',
                    ])
                );

                if (array_key_exists('name', $payload)) {
                    $payload['name'] = trim($payload['name']);
                }

                if (array_key_exists('code', $payload)) {
                    $payload['code'] = $this->normalizeCode(
                        $payload['code']
                    );
                }

                if (array_key_exists('description', $payload)) {
                    $payload['description'] = $this->normalizeDescription(
                        $payload['description']
                    );
                }

                $payload['updated_by'] = Auth::id();

                $department->fill($payload);
                $department->save();

                return $department->fresh();
            }
        );
    }

    /**
     * Soft-delete a department.
     */
    public function delete(Department $department): void
    {
        DB::connection('tenant')->transaction(
            fn () => $department->delete()
        );
    }

    /**
     * Activate a department.
     */
    public function activate(Department $department): Department
    {
        return $this->setActiveStatus($department, true);
    }

    /**
     * Deactivate a department.
     */
    public function deactivate(Department $department): Department
    {
        return $this->setActiveStatus($department, false);
    }

    /**
     * Update a department's display order.
     */
    public function updateSortOrder(
        Department $department,
        int $sortOrder
    ): Department {
        return DB::connection('tenant')->transaction(
            function () use ($department, $sortOrder): Department {
                $department->fill([
                    'sort_order' => $sortOrder,
                    'updated_by' => Auth::id(),
                ]);

                $department->save();

                return $department->fresh();
            }
        );
    }

    /**
     * Normalize a department code.
     */
    private function normalizeCode(string $code): string
    {
        return strtoupper(trim($code));
    }

    /**
     * Normalize an optional description.
     */
    private function normalizeDescription(?string $description): ?string
    {
        if ($description === null) {
            return null;
        }

        $description = trim($description);

        return $description === '' ? null : $description;
    }

    /**
     * Set the active status of a department.
     */
    private function setActiveStatus(
        Department $department,
        bool $status
    ): Department {
        return DB::connection('tenant')->transaction(
            function () use ($department, $status): Department {
                $department->fill([
                    'is_active' => $status,
                    'updated_by' => Auth::id(),
                ]);

                $department->save();

                return $department->fresh();
            }
        );
    }

    /**
     * Validate department name and code uniqueness.
     */
    private function validateUniqueness(
        array $data,
        ?Department $department = null
    ): void {
        $query = Department::query();

        if ($department !== null) {
            $query->whereKeyNot($department->getKey());
        }

        if (
            isset($data['name']) &&
            (clone $query)
                ->where('name', trim($data['name']))
                ->exists()
        ) {
            throw ValidationException::withMessages([
                'name' => [
                    'A department with this name already exists.',
                ],
            ]);
        }

        if (
            isset($data['code']) &&
            (clone $query)
                ->where(
                    'code',
                    $this->normalizeCode($data['code'])
                )
                ->exists()
        ) {
            throw ValidationException::withMessages([
                'code' => [
                    'A department with this code already exists.',
                ],
            ]);
        }
    }
}