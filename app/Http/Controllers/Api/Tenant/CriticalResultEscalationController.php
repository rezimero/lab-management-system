<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Notification\CriticalResultActionRequest;
use App\Http\Resources\Notification\CriticalResultEscalationResource;
use App\Models\CriticalResultEscalation;
use App\Models\Tenant\User as TenantUser;
use App\Services\Tenant\Notification\CriticalResultEscalationService;
use App\Support\Tenant\TenantUserPermissionCatalog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class CriticalResultEscalationController extends Controller
{
    public function __construct(
        private readonly CriticalResultEscalationService $escalations
    ) {}

    public function index(
        Request $request
    ): AnonymousResourceCollection {
        $this->user($request);
        $perPage = min(
            50,
            max(1, (int) $request->integer('per_page', 20))
        );
        $status = trim((string) $request->query('status', ''));
        $criticalUuid = trim((string) $request->query('critical', ''));

        if ($criticalUuid !== '' && ! Str::isUuid($criticalUuid)) {
            abort(422, 'The critical-result identifier is invalid.');
        }

        return CriticalResultEscalationResource::collection(
            CriticalResultEscalation::query()
                ->with([
                    'testResult.orderItem.order.patient',
                ])
                ->when(
                    $criticalUuid !== '',
                    static fn ($query) => $query->where(
                        'uuid',
                        $criticalUuid
                    )
                )
                ->when(
                    in_array(
                        $status,
                        [
                            CriticalResultEscalation::STATUS_OPEN,
                            CriticalResultEscalation::STATUS_ACKNOWLEDGED,
                            CriticalResultEscalation::STATUS_RESOLVED,
                        ],
                        true
                    ),
                    static fn ($query) => $query->where(
                        'status',
                        $status
                    )
                )
                ->orderByRaw(
                    "CASE status WHEN 'open' THEN 0 WHEN 'acknowledged' THEN 1 ELSE 2 END"
                )
                ->latest('detected_at')
                ->paginate($perPage)
        );
    }

    public function acknowledge(
        CriticalResultActionRequest $request,
        string $escalation
    ): CriticalResultEscalationResource {
        $actor = $this->user($request);

        return new CriticalResultEscalationResource(
            $this->escalations->acknowledge(
                $escalation,
                $actor,
                (string) $request->validated('note')
            )
        );
    }

    public function resolve(
        CriticalResultActionRequest $request,
        string $escalation
    ): CriticalResultEscalationResource {
        $actor = $this->user($request);

        return new CriticalResultEscalationResource(
            $this->escalations->resolve(
                $escalation,
                $actor,
                (string) $request->validated('note')
            )
        );
    }

    private function user(Request $request): TenantUser
    {
        $user = $request->user();

        abort_unless($user instanceof TenantUser, 401, 'Unauthenticated.');
        abort_unless(
            $user->hasPermission(
                TenantUserPermissionCatalog::ACKNOWLEDGE_CRITICAL_RESULTS
            ),
            403,
            'You do not have permission to manage critical results.'
        );

        return $user;
    }
}
