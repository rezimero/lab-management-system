<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Analyzer\StoreAnalyzerMessageRequest;
use App\Http\Resources\AnalyzerMessageResource;
use App\Models\AnalyzerInstrument;
use App\Services\Tenant\Analyzer\AnalyzerIngestService;
use Illuminate\Http\JsonResponse;

class AnalyzerMachineIngestController extends Controller
{
    public function __construct(
        private readonly AnalyzerIngestService $ingest
    ) {}

    public function store(
        StoreAnalyzerMessageRequest $request
    ): JsonResponse {
        $instrument = $request->attributes->get(
            'analyzer_instrument'
        );

        abort_unless(
            $instrument instanceof AnalyzerInstrument,
            401,
            'Analyzer machine authentication failed.'
        );

        $result = $this->ingest->ingest(
            $request,
            $instrument,
            $request->validated()
        );

        $response = (
            new AnalyzerMessageResource($result['message'])
        )->additional([
            'message' => $result['duplicate']
                ? 'Analyzer message was already accepted.'
                : 'Analyzer message accepted for processing.',
            'duplicate' => $result['duplicate'],
        ])->response();

        return $response->setStatusCode(
            $result['duplicate'] ? 200 : 202
        );
    }
}
