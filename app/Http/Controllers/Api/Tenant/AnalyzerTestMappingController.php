<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Api\Tenant\Concerns\AuthorizesAnalyzers;
use App\Http\Controllers\Controller;
use App\Http\Requests\Analyzer\IndexAnalyzerTestMappingRequest;
use App\Http\Requests\Analyzer\StoreAnalyzerTestMappingRequest;
use App\Http\Resources\AnalyzerTestMappingResource;
use App\Models\AnalyzerInstrument;
use App\Models\AnalyzerTestMapping;
use App\Services\Tenant\Analyzer\AnalyzerMappingService;
use App\Services\Tenant\Analyzer\AnalyzerQueryService;
use App\Services\Tenant\EntitlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AnalyzerTestMappingController extends Controller
{
    use AuthorizesAnalyzers;

    public function __construct(
        protected AnalyzerQueryService $queries,
        protected AnalyzerMappingService $mappings,
        protected EntitlementService $entitlements
    ) {}

    public function index(
        IndexAnalyzerTestMappingRequest $request,
        AnalyzerInstrument $analyzer
    ): AnonymousResourceCollection {
        $this->ensureAnalyzerEntitlement($this->entitlements);
        $data = $request->validated();

        return AnalyzerTestMappingResource::collection(
            $this->queries->testMappings(
                $this->analyzerUser($request),
                $analyzer,
                $data['search'] ?? null,
                array_key_exists('active', $data)
                    ? (bool) $data['active']
                    : null,
                $data['lab_test_uuid'] ?? null,
                (int) ($data['per_page'] ?? 50)
            )
        );
    }

    public function store(
        StoreAnalyzerTestMappingRequest $request,
        AnalyzerInstrument $analyzer
    ): JsonResponse {
        $this->ensureAnalyzerEntitlement($this->entitlements);

        return (new AnalyzerTestMappingResource(
            $this->mappings->saveTestMapping(
                $analyzer,
                null,
                $request->validated(),
                $this->analyzerUser($request)
            )
        ))->additional([
            'message' => 'Analyzer test mapping created successfully.',
        ])->response()->setStatusCode(201);
    }

    public function update(
        StoreAnalyzerTestMappingRequest $request,
        AnalyzerInstrument $analyzer,
        AnalyzerTestMapping $testMapping
    ): JsonResponse {
        $this->ensureAnalyzerEntitlement($this->entitlements);

        return (new AnalyzerTestMappingResource(
            $this->mappings->saveTestMapping(
                $analyzer,
                $testMapping,
                $request->validated(),
                $this->analyzerUser($request)
            )
        ))->additional([
            'message' => 'Analyzer test mapping updated successfully.',
        ])->response();
    }
}
