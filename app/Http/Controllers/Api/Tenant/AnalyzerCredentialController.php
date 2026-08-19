<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Api\Tenant\Concerns\AuthorizesAnalyzers;
use App\Http\Controllers\Controller;
use App\Http\Resources\AnalyzerInstrumentResource;
use App\Models\AnalyzerInstrument;
use App\Services\Tenant\Analyzer\AnalyzerInstrumentService;
use App\Services\Tenant\EntitlementService;
use App\Support\Tenant\TenantUserPermissionCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyzerCredentialController extends Controller
{
    use AuthorizesAnalyzers;

    public function __construct(
        protected AnalyzerInstrumentService $instruments,
        protected EntitlementService $entitlements
    ) {}

    public function rotate(
        Request $request,
        AnalyzerInstrument $analyzer
    ): JsonResponse {
        $this->ensureAnalyzerEntitlement($this->entitlements);

        $actor = $this->ensureAnalyzerPermission(
            $request,
            TenantUserPermissionCatalog::ROTATE_ANALYZER_CREDENTIALS
        );

        $rotated = $this->instruments->rotateCredential(
            $analyzer,
            $actor
        );

        return (new AnalyzerInstrumentResource(
            $rotated['instrument']
        ))->additional([
            'message' => 'Analyzer machine credential rotated successfully.',
            'machine_token' => $rotated['machine_token'],
            'machine_token_notice' => (
                'Store this token securely. The previous token is no longer valid.'
            ),
        ])->response();
    }
}
