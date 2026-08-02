<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\StoreCorporateAccountRequest;
use App\Http\Requests\Billing\UpdateCorporateAccountRequest;
use App\Models\CorporateAccount;
use App\Models\Tenant;
use App\Models\Tenant\User as TenantUser;
use App\Services\Tenant\EntitlementService;
use App\Support\Commercial\CommercialFeatureCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CorporateAccountController extends Controller
{
    public function __construct(
        protected EntitlementService $entitlements,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->ensure($request, false);

        return response()->json([
            'data' => CorporateAccount::query()
                ->orderBy('name')
                ->paginate(min(100, max(1, (int) $request->input('per_page', 50)))),
        ]);
    }

    public function store(StoreCorporateAccountRequest $request): JsonResponse
    {
        $this->ensure($request, true);
        $user = $this->user($request);
        $data = $request->validated();
        $account = CorporateAccount::query()->create([
            ...$data,
            'code' => strtoupper(trim($data['code'])),
            'created_by' => $user->getKey(),
            'updated_by' => $user->getKey(),
        ]);

        return response()->json([
            'message' => 'Corporate account created successfully.',
            'data' => $account,
        ], 201);
    }

    public function update(
        UpdateCorporateAccountRequest $request,
        CorporateAccount $corporateAccount
    ): JsonResponse {
        $this->ensure($request, true);
        $data = $request->validated();
        $corporateAccount->fill([
            ...$data,
            'code' => strtoupper(trim($data['code'])),
            'updated_by' => $this->user($request)->getKey(),
        ])->save();

        return response()->json([
            'message' => 'Corporate account updated successfully.',
            'data' => $corporateAccount->fresh(),
        ]);
    }

    private function ensure(Request $request, bool $manage): void
    {
        $tenant = Tenant::current()
            ?? abort(422, 'Tenant context is required.');
        $this->entitlements->ensure($tenant, CommercialFeatureCatalog::BILLING);
        $user = $this->user($request);

        if (! $user->hasPermission($manage ? 'manage_billing' : 'view_billing')) {
            abort(403, 'Unauthorized.');
        }
    }

    private function user(Request $request): TenantUser
    {
        $user = $request->user();

        return $user instanceof TenantUser
            ? $user
            : abort(401, 'Unauthenticated.');
    }
}
