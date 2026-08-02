<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\CreateInvoiceRequest;
use App\Http\Requests\Billing\IndexInvoiceRequest;
use App\Http\Resources\InvoiceResource;
use App\Models\Tenant;
use App\Models\Tenant\User as TenantUser;
use App\Services\Tenant\Billing\InvoiceQueryService;
use App\Services\Tenant\Billing\InvoiceService;
use App\Services\Tenant\EntitlementService;
use App\Support\Commercial\CommercialFeatureCatalog;
use App\Support\Tenant\TenantUserPermissionCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InvoiceController extends Controller
{
    public function __construct(
        protected InvoiceQueryService $queries,
        protected InvoiceService $service,
        protected EntitlementService $entitlements,
    ) {}

    public function index(IndexInvoiceRequest $request): AnonymousResourceCollection
    {
        $this->ensureEntitlement();
        $validated = $request->validated();

        return InvoiceResource::collection(
            $this->queries->paginate(
                $this->user($request),
                $validated['search'] ?? null,
                $validated['status'] ?? null,
                $validated['branch_uuid'] ?? null,
                (int) ($validated['per_page'] ?? 25)
            )
        );
    }

    public function store(CreateInvoiceRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $invoice = $this->service->createFromOrder(
            $this->tenant(),
            $validated['laboratory_order_uuid'],
            $validated,
            $this->user($request)
        );

        return (new InvoiceResource($invoice))
            ->additional(['message' => 'Invoice created successfully.'])
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, string $invoice): InvoiceResource
    {
        $this->ensureEntitlement();
        $user = $this->user($request);
        $this->ensurePermission(
            $user,
            TenantUserPermissionCatalog::VIEW_BILLING
        );

        return new InvoiceResource(
            $this->queries->find($user, $invoice)
        );
    }

    public function issue(Request $request, string $invoice): JsonResponse
    {
        $this->ensureEntitlement();
        $user = $this->user($request);
        $this->ensurePermission(
            $user,
            TenantUserPermissionCatalog::MANAGE_BILLING
        );
        $authorizedInvoice = $this->queries->find($user, $invoice);

        return (new InvoiceResource(
            $this->service->issue($authorizedInvoice, $user)
        ))->additional(['message' => 'Invoice issued successfully.'])
            ->response();
    }

    public function void(Request $request, string $invoice): JsonResponse
    {
        $this->ensureEntitlement();
        $user = $this->user($request);
        $this->ensurePermission(
            $user,
            TenantUserPermissionCatalog::MANAGE_BILLING
        );
        $authorizedInvoice = $this->queries->find($user, $invoice);

        return (new InvoiceResource(
            $this->service->void($authorizedInvoice, $user)
        ))->additional(['message' => 'Invoice voided successfully.'])
            ->response();
    }

    private function ensureEntitlement(): void
    {
        $this->entitlements->ensure(
            $this->tenant(),
            CommercialFeatureCatalog::BILLING
        );
    }

    private function ensurePermission(
        TenantUser $user,
        string $permission
    ): void {
        if (! $user->hasPermission($permission)) {
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

    private function tenant(): Tenant
    {
        return Tenant::current()
            ?? abort(422, 'Tenant context is required.');
    }
}
