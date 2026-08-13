<?php

namespace App\Services\Tenant\Portal;

use App\Models\ReferringClinician;
use App\Models\Tenant\User as TenantUser;
use Illuminate\Support\Facades\DB;

class ReferringClinicianService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(
        array $data,
        TenantUser $user
    ): ReferringClinician {
        return DB::connection('tenant')->transaction(
            function () use ($data, $user): ReferringClinician {
                return ReferringClinician::query()->create([
                    'name' => $data['name'],
                    'email' => $data['email'] ?? null,
                    'phone' => $data['phone'] ?? null,
                    'organisation' => $data['organisation'] ?? null,
                    'registration_number' => (
                        $data['registration_number'] ?? null
                    ),
                    'specialty' => $data['specialty'] ?? null,
                    'is_active' => (bool) (
                        $data['is_active'] ?? true
                    ),
                    'created_by' => $user->getKey(),
                    'updated_by' => $user->getKey(),
                ])->fresh();
            }
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(
        ReferringClinician $clinician,
        array $data,
        TenantUser $user
    ): ReferringClinician {
        return DB::connection('tenant')->transaction(
            function () use (
                $clinician,
                $data,
                $user
            ): ReferringClinician {
                $clinician->fill([
                    'name' => $data['name'],
                    'email' => $data['email'] ?? null,
                    'phone' => $data['phone'] ?? null,
                    'organisation' => $data['organisation'] ?? null,
                    'registration_number' => (
                        $data['registration_number'] ?? null
                    ),
                    'specialty' => $data['specialty'] ?? null,
                    'is_active' => array_key_exists(
                        'is_active',
                        $data
                    )
                        ? (bool) $data['is_active']
                        : $clinician->is_active,
                    'updated_by' => $user->getKey(),
                ]);

                $clinician->save();

                return $clinician->fresh();
            }
        );
    }

    public function setActive(
        ReferringClinician $clinician,
        bool $isActive,
        TenantUser $user
    ): ReferringClinician {
        $clinician->forceFill([
            'is_active' => $isActive,
            'updated_by' => $user->getKey(),
        ])->save();

        return $clinician->fresh();
    }
}
