<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Api\Tenant\Concerns\AuthorizesAnalyzers;
use App\Http\Controllers\Controller;
use App\Http\Requests\Analyzer\IndexAnalyzerInstrumentRequest;
use App\Http\Requests\Analyzer\StoreAnalyzerInstrumentRequest;
use App\Http\Resources\AnalyzerInstrumentResource;
use App\Models\AnalyzerInstrument;
use App\Services\Tenant\Analyzer\AnalyzerInstrumentService;
use App\Services\Tenant\Analyzer\AnalyzerQueryService;
use App\Services\Tenant\EntitlementService;
use App\Support\Tenant\TenantUserPermissionCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AnalyzerInstrumentController extends Controller
{
    use AuthorizesAnalyzers;

    public function __construct(
        protected AnalyzerQueryService $queries,
        protected AnalyzerInstrumentService $instruments,
        protected EntitlementService $entitlements
    ) {}

    public function index(
        IndexAnalyzerInstrumentRequest $request
    ): AnonymousResourceCollection {
        $this->ensureAnalyzerEntitlement($this->entitlements);
        $data = $request->validated();

        return AnalyzerInstrumentResource::collection(
            $this->queries->instruments(
                $this->analyzerUser($request),
                $data['search'] ?? null,
                $data['branch_uuid'] ?? null,
                array_key_exists('active', $data)
                    ? (bool) $data['active']
                    : null,
                (int) ($data['per_page'] ?? 50)
            )
        );
    }

    public function store(
        StoreAnalyzerInstrumentRequest $request
    ): JsonResponse {
        $this->ensureAnalyzerEntitlement($this->entitlements);

        $created = $this->instruments->create(
            $request->validated(),
            $this->analyzerUser($request)
        );

        return (new AnalyzerInstrumentResource(
            $created['instrument']
        ))->additional([
            'message' => 'Analyzer instrument created successfully.',
            'machine_token' => $created['machine_token'],
            'machine_token_notice' => (
                'Store this token securely. It will not be shown again.'
            ),
        ])->response()->setStatusCode(201);
    }

    public function show(
        Request $request,
        AnalyzerInstrument $analyzer
    ): AnalyzerInstrumentResource {
        $this->ensureAnalyzerEntitlement($this->entitlements);

        $user = $this->ensureAnalyzerPermission(
            $request,
            TenantUserPermissionCatalog::VIEW_ANALYZERS
        );

        return new AnalyzerInstrumentResource(
            $this->queries->instrument($user, $analyzer)
        );
    }

    public function update(
        StoreAnalyzerInstrumentRequest $request,
        AnalyzerInstrument $analyzer
    ): JsonResponse {
        $this->ensureAnalyzerEntitlement($this->entitlements);

        return (new AnalyzerInstrumentResource(
            $this->instruments->update(
                $analyzer,
                $request->validated(),
                $this->analyzerUser($request)
            )
        ))->additional([
            'message' => 'Analyzer instrument updated successfully.',
        ])->response();
    }

    public function deactivate(
        Request $request,
        AnalyzerInstrument $analyzer
    ): JsonResponse {
        $this->ensureAnalyzerEntitlement($this->entitlements);

        $actor = $this->ensureAnalyzerPermission(
            $request,
            TenantUserPermissionCatalog::MANAGE_ANALYZERS
        );

        return (new AnalyzerInstrumentResource(
            $this->instruments->deactivate(
                $analyzer,
                $actor
            )
        ))->additional([
            'message' => 'Analyzer instrument deactivated successfully.',
        ])->response();
    }
}
