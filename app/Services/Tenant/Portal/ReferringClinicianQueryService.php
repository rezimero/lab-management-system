<?php

namespace App\Services\Tenant\Portal;

use App\Models\ReferringClinician;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ReferringClinicianQueryService
{
    public function paginate(
        ?string $search = null,
        ?bool $isActive = null,
        int $perPage = 25
    ): LengthAwarePaginator {
        return ReferringClinician::query()
            ->when(
                $search,
                function ($query, string $value): void {
                    $query->where(
                        function ($query) use ($value): void {
                            $like = '%'.$value.'%';

                            $query
                                ->where('name', 'like', $like)
                                ->orWhere('email', 'like', $like)
                                ->orWhere('phone', 'like', $like)
                                ->orWhere(
                                    'organisation',
                                    'like',
                                    $like
                                )
                                ->orWhere(
                                    'registration_number',
                                    'like',
                                    $like
                                )
                                ->orWhere(
                                    'specialty',
                                    'like',
                                    $like
                                );
                        }
                    );
                }
            )
            ->when(
                $isActive !== null,
                fn ($query) => $query->where(
                    'is_active',
                    $isActive
                )
            )
            ->orderBy('name')
            ->orderBy('id')
            ->paginate($perPage);
    }

    public function find(
        string $uuid
    ): ReferringClinician {
        return ReferringClinician::query()
            ->where('uuid', $uuid)
            ->firstOrFail();
    }
}
