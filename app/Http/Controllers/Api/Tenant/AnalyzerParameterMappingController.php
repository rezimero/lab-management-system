<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Api\Tenant\Concerns\AuthorizesAnalyzers;
use App\Http\Controllers\Controller;
use App\Http\Requests\Analyzer\IndexAnalyzerParameterMappingRequest;
use App\Http\Requests\Analyzer\StoreAnalyzerParameterMappingRequest;
use App\Http\Resources\AnalyzerParameterMappingResource;
use App\Models\AnalyzerInstrument;
use App\Models\AnalyzerParameterMapping;
use App\Models\AnalyzerTestMapping;
use App\Services\Tenant\Analyzer\AnalyzerMappingService;
use App\Services\Tenant\Analyzer\AnalyzerQueryService;
use App\Services\Tenant\EntitlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AnalyzerParameterMappingController extends Controller
{
    use AuthorizesAnalyzers;

    public function __construct(
        protected AnalyzerQueryService $queries,
        protected AnalyzerMappingService $mappings,
        protected EntitlementService $entitlements
    ) {}

    public function index(
        IndexAnalyzerParameterMappingRequest $request,
        AnalyzerInstrument $analyzer,
        AnalyzerTestMapping $testMapping
    ): AnonymousResourceCollection {
        $this->ensureAnalyzerEntitlement($this->entitlements);
        $data = $request->validated();

        return AnalyzerParameterMappingResource::collection(
            $this->queries->parameterMappings(
                $this->analyzerUser($request),
                $analyzer,
                $testMapping,
                $data['search'] ?? null,
                array_key_exists('active', $data)
                    ? (bool) $data['active']
                    : null,
                (int) ($data['per_page'] ?? 100)
            )
        );
    }

    public function store(
        StoreAnalyzerParameterMappingRequest $request,
        AnalyzerInstrument $analyzer,
        AnalyzerTestMapping $testMapping
    ): JsonResponse {
        $this->ensureAnalyzerEntitlement($this->entitlements);

        $this->assertOwnership($analyzer, $testMapping);

        return (new AnalyzerParameterMappingResource(
            $this->mappings->saveParameterMapping(
                $testMapping,
                null,
                $request->validated(),
                $this->analyzerUser($request)
            )
        ))->additional([
            'message' => 'Analyzer parameter mapping created successfully.',
        ])->response()->setStatusCode(201);
    }

    public function update(
        StoreAnalyzerParameterMappingRequest $request,
        AnalyzerInstrument $analyzer,
        AnalyzerTestMapping $testMapping,
        AnalyzerParameterMapping $parameterMapping
    ): JsonResponse {
        $this->ensureAnalyzerEntitlement($this->entitlements);

        $this->assertOwnership($analyzer, $testMapping);

        return (new AnalyzerParameterMappingResource(
            $this->mappings->saveParameterMapping(
                $testMapping,
                $parameterMapping,
                $request->validated(),
                $this->analyzerUser($request)
            )
        ))->additional([
            'message' => 'Analyzer parameter mapping updated successfully.',
        ])->response();
    }

    private function assertOwnership(
        AnalyzerInstrument $instrument,
        AnalyzerTestMapping $mapping
    ): void {
        if (
            (int) $mapping->analyzer_instrument_id
                !== (int) $instrument->getKey()
        ) {
            abort(404);
        }
    }
}
