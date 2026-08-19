<?php

namespace App\Http\Middleware;

use App\Models\AnalyzerInstrument;
use App\Models\Tenant;
use App\Services\Tenant\EntitlementService;
use App\Support\Commercial\CommercialFeatureCatalog;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateAnalyzerMachine
{
    public function __construct(
        private readonly EntitlementService $entitlements
    ) {}

    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $tenant = Tenant::current();

        if (! $tenant instanceof Tenant) {
            return $this->unauthorized();
        }

        if (
            ! $this->entitlements->allows(
                $tenant,
                CommercialFeatureCatalog::ANALYZER_INTEGRATION
            )
        ) {
            return response()->json([
                'message' => 'Analyzer integration is not enabled.',
                'error_code' => 'ANALYZER_ENTITLEMENT_DISABLED',
            ], 403);
        }

        $analyzerUuid = trim(
            (string) $request->header('X-Analyzer-Id')
        );
        $token = (string) $request->bearerToken();

        if ($analyzerUuid === '' || $token === '') {
            return $this->unauthorized();
        }

        $instrument = AnalyzerInstrument::query()
            ->where('uuid', $analyzerUuid)
            ->first();

        if (
            ! $instrument instanceof AnalyzerInstrument
            || ! $instrument->is_active
            || blank($instrument->machine_token_hash)
            || ! Hash::check(
                $token,
                (string) $instrument->machine_token_hash
            )
        ) {
            return $this->unauthorized();
        }

        $instrument->forceFill([
            'last_seen_at' => now(),
        ])->save();

        $request->attributes->set(
            'analyzer_instrument',
            $instrument
        );

        return $next($request);
    }

    private function unauthorized(): JsonResponse
    {
        return response()->json([
            'message' => 'Analyzer machine authentication failed.',
            'error_code' => 'ANALYZER_AUTH_FAILED',
        ], 401);
    }
}
