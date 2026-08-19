<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Api\Tenant\Concerns\AuthorizesAnalyzers;
use App\Http\Controllers\Controller;
use App\Http\Requests\Analyzer\IndexAnalyzerImportRequest;
use App\Http\Requests\Analyzer\RejectAnalyzerImportRequest;
use App\Http\Resources\AnalyzerResultImportResource;
use App\Models\AnalyzerResultImport;
use App\Services\Tenant\Analyzer\AnalyzerImportReviewService;
use App\Services\Tenant\Analyzer\AnalyzerQueryService;
use App\Services\Tenant\EntitlementService;
use App\Support\Tenant\TenantUserPermissionCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AnalyzerImportController extends Controller
{
    use AuthorizesAnalyzers;

    public function __construct(
        private readonly AnalyzerQueryService $queries,
        private readonly AnalyzerImportReviewService $reviews,
        private readonly EntitlementService $entitlements
    ) {}

    public function index(
        IndexAnalyzerImportRequest $request
    ): AnonymousResourceCollection {
        $this->ensureAnalyzerEntitlement($this->entitlements);
        $data = $request->validated();

        return AnalyzerResultImportResource::collection(
            $this->queries->imports(
                $this->analyzerUser($request),
                $data['search'] ?? null,
                $data['status'] ?? null,
                $data['analyzer_uuid'] ?? null,
                $data['sample_id'] ?? null,
                (int) ($data['per_page'] ?? 50)
            )
        )->additional([
            'statistics' => $this->queries->importStatistics(
                $this->analyzerUser($request)
            ),
        ]);
    }

    public function show(
        Request $request,
        AnalyzerResultImport $analyzerImport
    ): AnalyzerResultImportResource {
        $this->ensureAnalyzerEntitlement($this->entitlements);

        $user = $this->ensureAnalyzerPermission(
            $request,
            TenantUserPermissionCatalog::VIEW_ANALYZER_IMPORTS
        );

        return new AnalyzerResultImportResource(
            $this->queries->resultImport(
                $user,
                $analyzerImport
            )
        );
    }

    public function reprocess(
        Request $request,
        AnalyzerResultImport $analyzerImport
    ): JsonResponse {
        $this->ensureAnalyzerEntitlement($this->entitlements);

        $actor = $this->ensureAnalyzerPermission(
            $request,
            TenantUserPermissionCatalog::REPROCESS_ANALYZER_IMPORTS
        );

        $visible = $this->queries->resultImport(
            $actor,
            $analyzerImport
        );

        return (new AnalyzerResultImportResource(
            $this->reviews->reprocess(
                $visible,
                $actor
            )
        ))->additional([
            'message' => 'Analyzer import reprocessing completed or queued.',
        ])->response();
    }

    public function reject(
        RejectAnalyzerImportRequest $request,
        AnalyzerResultImport $analyzerImport
    ): JsonResponse {
        $this->ensureAnalyzerEntitlement($this->entitlements);

        $actor = $this->analyzerUser($request);

        $visible = $this->queries->resultImport(
            $actor,
            $analyzerImport
        );

        return (new AnalyzerResultImportResource(
            $this->reviews->reject(
                $visible,
                $actor,
                $request->validated('note')
            )
        ))->additional([
            'message' => 'Analyzer import rejected.',
        ])->response();
    }
}
