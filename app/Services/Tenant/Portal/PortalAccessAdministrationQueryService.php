<?php

namespace App\Services\Tenant\Portal;

use App\Models\Patient;
use App\Models\ReferringClinician;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PortalAccessAdministrationQueryService
{
    public function paginatePatients(
        ?string $search,
        ?bool $isActive,
        int $perPage
    ): LengthAwarePaginator {
        return Patient::query()
            ->with('portalAccount.latestInvitation')
            ->when(
                $isActive !== null,
                fn ($query) => $query->where(
                    'is_active',
                    $isActive
                )
            )
            ->when(
                filled($search),
                function ($query) use ($search): void {
                    $like = '%'.trim((string) $search).'%';

                    $query->where(
                        function ($query) use ($like): void {
                            $query->where('first_name', 'like', $like)
                                ->orWhere('last_name', 'like', $like)
                                ->orWhere('email', 'like', $like)
                                ->orWhere(
                                    'medical_record_number',
                                    'like',
                                    $like
                                );
                        }
                    );
                }
            )
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function paginateClinicians(
        ?string $search,
        ?bool $isActive,
        int $perPage
    ): LengthAwarePaginator {
        return ReferringClinician::query()
            ->with('portalAccount.latestInvitation')
            ->when(
                $isActive !== null,
                fn ($query) => $query->where(
                    'is_active',
                    $isActive
                )
            )
            ->when(
                filled($search),
                function ($query) use ($search): void {
                    $like = '%'.trim((string) $search).'%';

                    $query->where(
                        function ($query) use ($like): void {
                            $query->where('name', 'like', $like)
                                ->orWhere('email', 'like', $like)
                                ->orWhere('organisation', 'like', $like)
                                ->orWhere(
                                    'registration_number',
                                    'like',
                                    $like
                                );
                        }
                    );
                }
            )
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }
}
