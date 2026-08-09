<?php

namespace App\Services\Tenant\Referral;

use App\Models\LabTest;
use App\Models\ReferralLaboratory;
use App\Models\ReferralLaboratoryTest;
use App\Models\Tenant\User as TenantUser;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReferralLaboratoryService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function saveLaboratory(
        ?ReferralLaboratory $laboratory,
        array $data,
        TenantUser $actor
    ): ReferralLaboratory {
        return DB::connection('tenant')->transaction(
            function () use ($laboratory, $data, $actor): ReferralLaboratory {
                $laboratory ??= new ReferralLaboratory;

                $data = $this->normalizeNullableStrings($data, [
                    'accreditation_number',
                    'contact_name',
                    'email',
                    'phone',
                    'address_line_1',
                    'address_line_2',
                    'city',
                    'state',
                    'notes',
                ]);

                $data['is_active'] = (bool) (
                    $data['is_active'] ?? ($laboratory->exists
                        ? $laboratory->is_active
                        : true)
                );
                $data['updated_by'] = $actor->getKey();
                $data['created_by'] ??= $laboratory->exists
                    ? $laboratory->created_by
                    : $actor->getKey();

                $laboratory->fill($data)->save();

                return $laboratory->fresh();
            },
            3
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function saveTestMapping(
        ReferralLaboratory $laboratory,
        ?ReferralLaboratoryTest $mapping,
        array $data,
        TenantUser $actor
    ): ReferralLaboratoryTest {
        return DB::connection('tenant')->transaction(
            function () use (
                $laboratory,
                $mapping,
                $data,
                $actor
            ): ReferralLaboratoryTest {
                $laboratory = ReferralLaboratory::query()
                    ->lockForUpdate()
                    ->findOrFail($laboratory->getKey());

                if (
                    $mapping !== null
                    && (int) $mapping->referral_laboratory_id
                        !== (int) $laboratory->getKey()
                ) {
                    abort(404);
                }

                $labTest = LabTest::query()
                    ->where('uuid', $data['lab_test_uuid'])
                    ->lockForUpdate()
                    ->first();

                if ($labTest === null) {
                    throw ValidationException::withMessages([
                        'lab_test_uuid' => [
                            'The selected laboratory test does not exist.',
                        ],
                    ]);
                }

                $willBeActive = (bool) (
                    $data['is_active'] ?? ($mapping?->is_active ?? true)
                );
                $isCreating = $mapping === null;
                $changesLabTest = $mapping !== null
                    && (int) $mapping->lab_test_id
                        !== (int) $labTest->getKey();

                if (
                    ($isCreating || $willBeActive)
                    && ! $laboratory->is_active
                ) {
                    throw ValidationException::withMessages([
                        'is_active' => [
                            'Select an active referral laboratory before creating or activating a test mapping.',
                        ],
                    ]);
                }

                if (
                    ($isCreating || $willBeActive || $changesLabTest)
                    && ! $labTest->is_active
                ) {
                    throw ValidationException::withMessages([
                        'lab_test_uuid' => [
                            'Select an active local laboratory test.',
                        ],
                    ]);
                }

                $duplicate = ReferralLaboratoryTest::query()
                    ->where(
                        'referral_laboratory_id',
                        $laboratory->getKey()
                    )
                    ->where('lab_test_id', $labTest->getKey())
                    ->when(
                        $mapping !== null,
                        fn ($query) => $query->where(
                            'id',
                            '!=',
                            $mapping->getKey()
                        )
                    )
                    ->exists();

                if ($duplicate) {
                    throw ValidationException::withMessages([
                        'lab_test_uuid' => [
                            'This local laboratory test is already mapped for the selected referral laboratory.',
                        ],
                    ]);
                }

                $mapping ??= new ReferralLaboratoryTest;
                unset($data['lab_test_uuid']);

                $data['referral_laboratory_id'] = $laboratory->getKey();
                $data['lab_test_id'] = $labTest->getKey();
                $data['specimen_requirements'] = $this->cleanRequirements(
                    $data['specimen_requirements'] ?? null
                );
                $data['is_active'] = $willBeActive;
                $data['updated_by'] = $actor->getKey();
                $data['created_by'] ??= $mapping->exists
                    ? $mapping->created_by
                    : $actor->getKey();

                $mapping->fill($data)->save();

                return $mapping->fresh([
                    'referralLaboratory',
                    'labTest',
                ]);
            },
            3
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $keys
     * @return array<string, mixed>
     */
    private function normalizeNullableStrings(
        array $data,
        array $keys
    ): array {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $data)) {
                continue;
            }

            $value = trim((string) $data[$key]);
            $data[$key] = $value === '' ? null : $value;
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>|null  $requirements
     * @return array<string, mixed>|null
     */
    private function cleanRequirements(?array $requirements): ?array
    {
        if ($requirements === null) {
            return null;
        }

        $cleaned = collect(Arr::only($requirements, [
            'specimen_type',
            'container',
            'minimum_volume',
            'instructions',
        ]))
            ->map(static function (mixed $value): mixed {
                if (! is_string($value)) {
                    return $value;
                }

                $value = trim($value);

                return $value === '' ? null : $value;
            })
            ->filter(static fn (mixed $value): bool => $value !== null)
            ->all();

        return $cleaned === [] ? null : $cleaned;
    }
}
