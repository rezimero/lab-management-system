<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\RecordPaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\Tenant\User as TenantUser;
use App\Services\Tenant\Billing\InvoiceQueryService;
use App\Services\Tenant\Billing\PaymentService;
use App\Services\Tenant\EntitlementService;
use App\Support\Commercial\CommercialFeatureCatalog;
use App\Support\Tenant\TenantUserPermissionCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $service,
        protected InvoiceQueryService $invoices,
        protected EntitlementService $entitlements,
    ) {}

    public function store(
        RecordPaymentRequest $request,
        string $invoice
    ): JsonResponse {
        $tenant = $this->tenant();
        $this->entitlements->ensure(
            $tenant,
            CommercialFeatureCatalog::BILLING
        );
        $user = $this->user($request);
        $authorizedInvoice = $this->invoices->find($user, $invoice);
        $payment = $this->service->record(
            $tenant,
            $authorizedInvoice,
            $request->validated(),
            $user
        );

        return (new PaymentResource($payment))
            ->additional([
                'message' => 'Payment recorded and receipt issued successfully.',
            ])
            ->response()
            ->setStatusCode(201);
    }

    public function receipt(Request $request, string $payment): PaymentResource
    {
        $this->entitlements->ensure(
            $this->tenant(),
            CommercialFeatureCatalog::BILLING
        );
        $user = $this->user($request);

        if (! $user->hasPermission(
            TenantUserPermissionCatalog::VIEW_BILLING
        )) {
            abort(403, 'Unauthorized.');
        }

        $assignedBranchIds = $user->isAdmin()
            ? []
            : $user->branches()
                ->pluck('branches.id')
                ->map(static fn ($id): int => (int) $id)
                ->all();

        $authorizedPayment = Payment::query()
            ->with([
                'invoice.order',
                'invoice.patient',
                'invoice.branch',
                'invoice.corporateAccount',
                'receivedBy',
            ])
            ->when(
                ! $user->isAdmin(),
                fn ($query) => $query->whereHas(
                    'invoice',
                    fn ($query) => $query->whereIn(
                        'branch_id',
                        $assignedBranchIds
                    )
                )
            )
            ->where('uuid', $payment)
            ->firstOrFail();

        return new PaymentResource($authorizedPayment);
    }

    private function user(Request $request): TenantUser
    {
        $user = $request->user();

        return $user instanceof TenantUser
            ? $user
            : abort(401, 'Unauthenticated.');
    }

    private function tenant(): Tenant
    {
        return Tenant::current()
            ?? abort(422, 'Tenant context is required.');
    }
}
