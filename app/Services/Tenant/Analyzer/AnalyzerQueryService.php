<?php

namespace App\Services\Tenant\Analyzer;

use App\Models\AnalyzerInstrument;
use App\Models\AnalyzerParameterMapping;
use App\Models\AnalyzerResultImport;
use App\Models\AnalyzerTestMapping;
use App\Models\Tenant\User as TenantUser;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class AnalyzerQueryService
{
    public function instruments(
        TenantUser $user,
        ?string $search,
        ?string $branchUuid,
        ?bool $active,
        int $perPage
    ): LengthAwarePaginator {
        return $this->visibleInstrumentQuery($user)
            ->with('branch')
            ->withCount([
                'testMappings',
                'messages',
                'imports',
            ])
            ->when(
                $search,
                fn (Builder $query, string $value): Builder => $query
                    ->where(
                        fn (Builder $query): Builder => $query
                            ->where('name', 'like', "%{$value}%")
                            ->orWhere('code', 'like', "%{$value}%")
                            ->orWhere(
                                'manufacturer',
                                'like',
                                "%{$value}%"
                            )
                            ->orWhere('model', 'like', "%{$value}%")
                            ->orWhere(
                                'serial_number',
                                'like',
                                "%{$value}%"
                            )
                    )
            )
            ->when(
                $branchUuid,
                fn (Builder $query, string $uuid): Builder => $query
                    ->whereHas(
                        'branch',
                        fn (Builder $branch): Builder => $branch
                            ->where('uuid', $uuid)
                    )
            )
            ->when(
                $active !== null,
                fn (Builder $query): Builder => $query
                    ->where('is_active', $active)
            )
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function instrument(
        TenantUser $user,
        AnalyzerInstrument $instrument
    ): AnalyzerInstrument {
        return $this->visibleInstrumentQuery($user)
            ->whereKey($instrument->getKey())
            ->with([
                'branch',
                'testMappings' => fn ($query) => $query
                    ->with(['labTest', 'parameterMappings'])
                    ->orderBy('instrument_test_code'),
            ])
            ->withCount([
                'testMappings',
                'messages',
                'imports',
            ])
            ->firstOrFail();
    }

    public function testMappings(
        TenantUser $user,
        AnalyzerInstrument $instrument,
        ?string $search,
        ?bool $active,
        ?string $labTestUuid,
        int $perPage
    ): LengthAwarePaginator {
        $this->visibleInstrumentQuery($user)
            ->whereKey($instrument->getKey())
            ->firstOrFail();

        return AnalyzerTestMapping::query()
            ->where(
                'analyzer_instrument_id',
                $instrument->getKey()
            )
            ->with([
                'instrument.branch',
                'labTest',
                'parameterMappings',
            ])
            ->withCount('parameterMappings')
            ->when(
                $search,
                fn (Builder $query, string $value): Builder => $query
                    ->where(
                        fn (Builder $query): Builder => $query
                            ->where(
                                'instrument_test_code',
                                'like',
                                "%{$value}%"
                            )
                            ->orWhere(
                                'instrument_test_name',
                                'like',
                                "%{$value}%"
                            )
                            ->orWhereHas(
                                'labTest',
                                fn (Builder $labTest): Builder => $labTest
                                    ->where('name', 'like', "%{$value}%")
                            )
                    )
            )
            ->when(
                $active !== null,
                fn (Builder $query): Builder => $query
                    ->where('is_active', $active)
            )
            ->when(
                $labTestUuid,
                fn (Builder $query, string $uuid): Builder => $query
                    ->whereHas(
                        'labTest',
                        fn (Builder $labTest): Builder => $labTest
                            ->where('uuid', $uuid)
                    )
            )
            ->orderBy('instrument_test_code')
            ->paginate($perPage);
    }

    public function parameterMappings(
        TenantUser $user,
        AnalyzerInstrument $instrument,
        AnalyzerTestMapping $testMapping,
        ?string $search,
        ?bool $active,
        int $perPage
    ): LengthAwarePaginator {
        $this->visibleInstrumentQuery($user)
            ->whereKey($instrument->getKey())
            ->firstOrFail();

        if (
            (int) $testMapping->analyzer_instrument_id
                !== (int) $instrument->getKey()
        ) {
            abort(404);
        }

        return AnalyzerParameterMapping::query()
            ->where(
                'analyzer_test_mapping_id',
                $testMapping->getKey()
            )
            ->with('testMapping.labTest')
            ->when(
                $search,
                fn (Builder $query, string $value): Builder => $query
                    ->where(
                        fn (Builder $query): Builder => $query
                            ->where(
                                'instrument_parameter_code',
                                'like',
                                "%{$value}%"
                            )
                            ->orWhere(
                                'instrument_parameter_name',
                                'like',
                                "%{$value}%"
                            )
                            ->orWhere(
                                'target_parameter_name',
                                'like',
                                "%{$value}%"
                            )
                    )
            )
            ->when(
                $active !== null,
                fn (Builder $query): Builder => $query
                    ->where('is_active', $active)
            )
            ->orderBy('target_parameter_name')
            ->paginate($perPage);
    }


    public function imports(
        TenantUser $user,
        ?string $search,
        ?string $status,
        ?string $analyzerUuid,
        ?string $sampleId,
        int $perPage
    ): LengthAwarePaginator {
        return $this->visibleImportQuery($user)
            ->with([
                'instrument',
                'message',
                'specimen',
                'orderItem.order',
                'testResult',
            ])
            ->when(
                $search,
                fn (Builder $query, string $value): Builder => $query
                    ->where(
                        fn (Builder $query): Builder => $query
                            ->where(
                                'source_result_key',
                                'like',
                                "%{$value}%"
                            )
                            ->orWhere(
                                'source_sample_identifier',
                                'like',
                                "%{$value}%"
                            )
                            ->orWhere(
                                'instrument_test_code',
                                'like',
                                "%{$value}%"
                            )
                            ->orWhereHas(
                                'instrument',
                                fn (Builder $instrument): Builder => (
                                    $instrument
                                        ->where(
                                            'name',
                                            'like',
                                            "%{$value}%"
                                        )
                                        ->orWhere(
                                            'code',
                                            'like',
                                            "%{$value}%"
                                        )
                                )
                            )
                    )
            )
            ->when(
                $status,
                fn (Builder $query, string $value): Builder => $query
                    ->where('status', $value)
            )
            ->when(
                $analyzerUuid,
                fn (Builder $query, string $uuid): Builder => $query
                    ->whereHas(
                        'instrument',
                        fn (Builder $instrument): Builder => $instrument
                            ->where('uuid', $uuid)
                    )
            )
            ->when(
                $sampleId,
                fn (Builder $query, string $value): Builder => $query
                    ->where(
                        'source_sample_identifier',
                        strtoupper($value)
                    )
            )
            ->latest('id')
            ->paginate($perPage);
    }

    /**
     * @return array<string, int>
     */
    public function importStatistics(
        TenantUser $user
    ): array {
        $counts = $this->visibleImportQuery($user)
            ->selectRaw(
                'status, COUNT(*) AS aggregate'
            )
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return [
            'total' => (int) $counts->sum(),
            'received' => (int) $counts->get(
                AnalyzerResultImport::STATUS_RECEIVED,
                0
            ),
            'needs_review' => (int) $counts->get(
                AnalyzerResultImport::STATUS_NEEDS_REVIEW,
                0
            ),
            'ready' => (int) $counts->get(
                AnalyzerResultImport::STATUS_READY,
                0
            ),
            'imported' => (int) $counts->get(
                AnalyzerResultImport::STATUS_IMPORTED,
                0
            ),
            'rejected' => (int) $counts->get(
                AnalyzerResultImport::STATUS_REJECTED,
                0
            ),
            'failed' => (int) $counts->get(
                AnalyzerResultImport::STATUS_FAILED,
                0
            ),
            'duplicate' => (int) $counts->get(
                AnalyzerResultImport::STATUS_DUPLICATE,
                0
            ),
        ];
    }

    public function resultImport(
        TenantUser $user,
        AnalyzerResultImport $resultImport
    ): AnalyzerResultImport {
        return $this->visibleImportQuery($user)
            ->whereKey($resultImport->getKey())
            ->with([
                'instrument',
                'message',
                'testMapping.labTest',
                'specimen',
                'orderItem.order',
                'testResult',
                'events' => fn ($query) => $query
                    ->orderBy('occurred_at')
                    ->orderBy('id'),
            ])
            ->firstOrFail();
    }

    private function visibleImportQuery(
        TenantUser $user
    ): Builder {
        return AnalyzerResultImport::query()
            ->when(
                ! $user->isAdmin()
                    && $user->role !== 'superadmin',
                fn (Builder $query): Builder => $query
                    ->whereHas(
                        'instrument.branch.users',
                        fn (Builder $users): Builder => $users
                            ->where(
                                'users.id',
                                $user->getKey()
                            )
                    )
            );
    }

    private function visibleInstrumentQuery(
        TenantUser $user
    ): Builder {
        return AnalyzerInstrument::query()
            ->when(
                ! $user->isAdmin()
                    && $user->role !== 'superadmin',
                fn (Builder $query): Builder => $query
                    ->whereHas(
                        'branch.users',
                        fn (Builder $users): Builder => $users
                            ->where(
                                'users.id',
                                $user->getKey()
                            )
                    )
            );
    }
}
