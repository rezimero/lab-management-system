<?php

namespace App\Services\Tenant\Analyzer;

use App\Models\AnalyzerInstrument;
use App\Models\Branch;
use App\Models\Tenant\User as TenantUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AnalyzerInstrumentService
{
    /**
     * @param  array<string, mixed>  $data
     * @return array{instrument: AnalyzerInstrument, machine_token: string}
     */
    public function create(
        array $data,
        TenantUser $actor
    ): array {
        return DB::connection('tenant')->transaction(
            function () use ($data, $actor): array {
                $branch = $this->resolveBranch($data['branch_uuid']);
                unset($data['branch_uuid']);

                $instrument = new AnalyzerInstrument;
                $instrument->fill([
                    ...$this->normalizedAttributes($data),
                    'branch_id' => $branch->getKey(),
                    'adapter_key' => $data['adapter_key']
                        ?? AnalyzerInstrument::ADAPTER_CANONICAL_JSON_V1,
                    'transport_mode' => $data['transport_mode']
                        ?? AnalyzerInstrument::TRANSPORT_GATEWAY,
                    'is_active' => true,
                    'created_by' => $actor->getKey(),
                    'updated_by' => $actor->getKey(),
                ]);

                $token = $this->generateToken();
                $instrument->machine_token_hash = Hash::make($token);
                $instrument->machine_token_hint = substr($token, -8);
                $instrument->token_rotated_at = now();
                $instrument->save();

                return [
                    'instrument' => $instrument->fresh('branch'),
                    'machine_token' => $token,
                ];
            },
            3
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(
        AnalyzerInstrument $instrument,
        array $data,
        TenantUser $actor
    ): AnalyzerInstrument {
        return DB::connection('tenant')->transaction(
            function () use (
                $instrument,
                $data,
                $actor
            ): AnalyzerInstrument {
                $instrument = AnalyzerInstrument::query()
                    ->lockForUpdate()
                    ->findOrFail($instrument->getKey());

                $branch = $this->resolveBranch($data['branch_uuid']);
                unset($data['branch_uuid']);

                $instrument->fill([
                    ...$this->normalizedAttributes($data),
                    'branch_id' => $branch->getKey(),
                    'updated_by' => $actor->getKey(),
                ])->save();

                return $instrument->fresh('branch');
            },
            3
        );
    }

    public function deactivate(
        AnalyzerInstrument $instrument,
        TenantUser $actor
    ): AnalyzerInstrument {
        return DB::connection('tenant')->transaction(
            function () use (
                $instrument,
                $actor
            ): AnalyzerInstrument {
                $instrument = AnalyzerInstrument::query()
                    ->lockForUpdate()
                    ->findOrFail($instrument->getKey());

                if (! $instrument->is_active) {
                    throw ValidationException::withMessages([
                        'analyzer' => [
                            'The analyzer instrument is already inactive.',
                        ],
                    ]);
                }

                $instrument->forceFill([
                    'is_active' => false,
                    'updated_by' => $actor->getKey(),
                ])->save();

                return $instrument->fresh('branch');
            },
            3
        );
    }

    /**
     * @return array{instrument: AnalyzerInstrument, machine_token: string}
     */
    public function rotateCredential(
        AnalyzerInstrument $instrument,
        TenantUser $actor
    ): array {
        return DB::connection('tenant')->transaction(
            function () use (
                $instrument,
                $actor
            ): array {
                $instrument = AnalyzerInstrument::query()
                    ->lockForUpdate()
                    ->findOrFail($instrument->getKey());

                if (! $instrument->is_active) {
                    throw ValidationException::withMessages([
                        'analyzer' => [
                            'Reactivate or replace this analyzer before rotating its machine credential.',
                        ],
                    ]);
                }

                $token = $this->generateToken();

                $instrument->forceFill([
                    'machine_token_hash' => Hash::make($token),
                    'machine_token_hint' => substr($token, -8),
                    'token_rotated_at' => now(),
                    'updated_by' => $actor->getKey(),
                ])->save();

                return [
                    'instrument' => $instrument->fresh('branch'),
                    'machine_token' => $token,
                ];
            },
            3
        );
    }

    private function resolveBranch(string $uuid): Branch
    {
        $branch = Branch::query()
            ->where('uuid', $uuid)
            ->where('type', 'laboratory')
            ->where('is_active', true)
            ->first();

        if ($branch === null) {
            throw ValidationException::withMessages([
                'branch_uuid' => [
                    'Select an active processing laboratory branch.',
                ],
            ]);
        }

        return $branch;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizedAttributes(array $data): array
    {
        $normalized = [
            'code' => strtoupper(trim((string) $data['code'])),
            'name' => trim((string) $data['name']),
        ];

        foreach ([
            'manufacturer',
            'model',
            'serial_number',
            'notes',
        ] as $key) {
            $value = $data[$key] ?? null;

            if ($value === null) {
                $normalized[$key] = null;
                continue;
            }

            $value = trim((string) $value);
            $normalized[$key] = $value === '' ? null : $value;
        }

        if (array_key_exists('adapter_key', $data)) {
            $normalized['adapter_key'] = strtolower(
                trim((string) $data['adapter_key'])
            );
        }

        if (array_key_exists('transport_mode', $data)) {
            $normalized['transport_mode'] = strtolower(
                trim((string) $data['transport_mode'])
            );
        }

        return $normalized;
    }

    private function generateToken(): string
    {
        return 'rza_'.rtrim(
            strtr(
                base64_encode(random_bytes(32)),
                '+/',
                '-_'
            ),
            '='
        );
    }
}
