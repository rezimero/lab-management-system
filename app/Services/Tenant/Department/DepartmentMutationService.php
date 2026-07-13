<?php

namespace App\Services\Tenant\Department;

use App\Models\Department;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DepartmentMutationService
{
    /**
     * Create a department in the current tenant database.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): Department
    {
        $this->validateUniqueness($data);

        return DB::connection('tenant')->transaction(
            function () use ($data): Department {
                $userId = Auth::id();

                $department = Department::query()->create([
                    'name' => trim($data['name']),
                    'code' => $this->normalizeCode(
                        $data['code']
                    ),
                    'description' => (
                        $this->normalizeDescription(
                            $data['description'] ?? null
                        )
                    ),
                    'sort_order' => (
                        $data['sort_order'] ?? 0
                    ),
                    'is_active' => (
                        $data['is_active'] ?? true
                    ),
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);

                return $department->fresh();
            }
        );
    }

    /**
     * Update a department in the current tenant database.
     *
     * @param array<string, mixed> $data
     */
    public function update(
        Department $department,
        array $data
    ): Department {
        $this->validateUniqueness(
            $data,
            $department
        );

        return DB::connection('tenant')->transaction(
            function () use (
                $department,
                $data
            ): Department {
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

                if (
                    array_key_exists(
                        'name',
                        $payload
                    )
                ) {
                    $payload['name'] = trim(
                        $payload['name']
                    );
                }

                if (
                    array_key_exists(
                        'code',
                        $payload
                    )
                ) {
                    $payload['code'] = (
                        $this->normalizeCode(
                            $payload['code']
                        )
                    );
                }

                if (
                    array_key_exists(
                        'description',
                        $payload
                    )
                ) {
                    $payload['description'] = (
                        $this->normalizeDescription(
                            $payload['description']
                        )
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
    public function delete(
        Department $department
    ): void {
        DB::connection('tenant')->transaction(
            fn () => $department->delete()
        );
    }

    /**
     * Activate a department.
     */
    public function activate(
        Department $department
    ): Department {
        return $this->setActiveStatus(
            $department,
            true
        );
    }

    /**
     * Deactivate a department.
     */
    public function deactivate(
        Department $department
    ): Department {
        return $this->setActiveStatus(
            $department,
            false
        );
    }

    private function normalizeCode(
        string $code
    ): string {
        return strtoupper(trim($code));
    }

    private function normalizeDescription(
        ?string $description
    ): ?string {
        if ($description === null) {
            return null;
        }

        $description = trim($description);

        return $description === ''
            ? null
            : $description;
    }

    private function setActiveStatus(
        Department $department,
        bool $status
    ): Department {
        return DB::connection('tenant')->transaction(
            function () use (
                $department,
                $status
            ): Department {
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
     *
     * @param array<string, mixed> $data
     */
    private function validateUniqueness(
        array $data,
        ?Department $department = null
    ): void {
        $query = Department::query();

        if ($department !== null) {
            $query->whereKeyNot(
                $department->getKey()
            );
        }

        if (
            isset($data['name'])
            && (clone $query)
                ->where(
                    'name',
                    trim($data['name'])
                )
                ->exists()
        ) {
            throw ValidationException::withMessages([
                'name' => [
                    'A department with this name already exists.',
                ],
            ]);
        }

        if (
            isset($data['code'])
            && (clone $query)
                ->where(
                    'code',
                    $this->normalizeCode(
                        $data['code']
                    )
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