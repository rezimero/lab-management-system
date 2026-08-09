<?php

namespace App\Services\Tenant\Referral;

use App\Models\LaboratoryOrderItem;
use App\Models\LaboratoryReferral;
use App\Models\ReferralLaboratory;
use App\Models\ReferralLaboratoryTest;
use App\Models\Tenant\User as TenantUser;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ReferralQueryService
{
    public function __construct(
        protected ReferralBranchAccessService $branchAccess
    ) {}

    public function laboratories(
        ?string $search,
        ?bool $active,
        int $perPage
    ): LengthAwarePaginator {
        return ReferralLaboratory::query()
            ->withCount(['testMappings', 'referrals'])
            ->when(
                $search,
                fn ($query, $value) => $query->where(
                    fn ($query) => $query
                        ->where('name', 'like', "%{$value}%")
                        ->orWhere('code', 'like', "%{$value}%")
                        ->orWhere(
                            'accreditation_number',
                            'like',
                            "%{$value}%"
                        )
                        ->orWhere('city', 'like', "%{$value}%")
                        ->orWhere('state', 'like', "%{$value}%")
                )
            )
            ->when(
                $active !== null,
                fn ($query) => $query->where('is_active', $active)
            )
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function laboratory(
        ReferralLaboratory $laboratory
    ): ReferralLaboratory {
        return $laboratory->load([
            'testMappings' => fn ($query) => $query
                ->with('labTest')
                ->orderBy('external_test_name'),
        ])->loadCount(['testMappings', 'referrals']);
    }

    public function mappings(
        ReferralLaboratory $laboratory,
        ?string $search,
        ?bool $active,
        ?string $labTestUuid,
        int $perPage
    ): LengthAwarePaginator {
        return ReferralLaboratoryTest::query()
            ->with(['referralLaboratory', 'labTest'])
            ->where(
                'referral_laboratory_id',
                $laboratory->getKey()
            )
            ->when(
                $search,
                fn ($query, $value) => $query->where(
                    fn ($query) => $query
                        ->where(
                            'external_test_code',
                            'like',
                            "%{$value}%"
                        )
                        ->orWhere(
                            'external_test_name',
                            'like',
                            "%{$value}%"
                        )
                        ->orWhereHas(
                            'labTest',
                            fn ($labTest) => $labTest
                                ->where('name', 'like', "%{$value}%")
                                ->orWhere(
                                    'category',
                                    'like',
                                    "%{$value}%"
                                )
                        )
                )
            )
            ->when(
                $active !== null,
                fn ($query) => $query->where('is_active', $active)
            )
            ->when(
                $labTestUuid,
                fn ($query, $value) => $query->whereHas(
                    'labTest',
                    fn ($labTest) => $labTest->where('uuid', $value)
                )
            )
            ->orderBy('external_test_name')
            ->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function referrals(
        array $filters,
        TenantUser $user
    ): LengthAwarePaginator {
        return $this->applyReferralFilters(
            $this->visibleReferralQuery($user),
            $filters
        )
            ->with($this->listRelations())
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate((int) ($filters['per_page'] ?? 25));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, int>
     */
    public function summary(
        array $filters,
        TenantUser $user
    ): array {
        $query = $this->applyReferralFilters(
            $this->visibleReferralQuery($user),
            $filters
        );

        return [
            'total' => (clone $query)->count(),
            'draft' => (clone $query)
                ->where('status', LaboratoryReferral::STATUS_DRAFT)
                ->count(),
            'dispatched' => (clone $query)
                ->where('status', LaboratoryReferral::STATUS_DISPATCHED)
                ->count(),
            'acknowledged' => (clone $query)
                ->where('status', LaboratoryReferral::STATUS_ACKNOWLEDGED)
                ->count(),
            'result_received' => (clone $query)
                ->where(
                    'status',
                    LaboratoryReferral::STATUS_RESULT_RECEIVED
                )
                ->count(),
            'completed' => (clone $query)
                ->where('status', LaboratoryReferral::STATUS_COMPLETED)
                ->count(),
            'rejected' => (clone $query)
                ->where('status', LaboratoryReferral::STATUS_REJECTED)
                ->count(),
            'cancelled' => (clone $query)
                ->where('status', LaboratoryReferral::STATUS_CANCELLED)
                ->count(),
            'overdue' => (clone $query)
                ->whereNotIn(
                    'status',
                    LaboratoryReferral::TERMINAL_STATUSES
                )
                ->whereNotNull('turnaround_due_at')
                ->where('turnaround_due_at', '<', now())
                ->count(),
        ];
    }

    public function referral(
        string $uuid,
        TenantUser $user
    ): LaboratoryReferral {
        return $this->visibleReferralQuery($user)
            ->with($this->detailRelations())
            ->where('uuid', $uuid)
            ->firstOrFail();
    }

    /**
     * @return Collection<int, LaboratoryReferral>
     */
    public function orderItemReferrals(
        string $orderItemUuid,
        TenantUser $user
    ): Collection {
        $item = $this->branchAccess->scopeVisibleOrderItems(
            LaboratoryOrderItem::query(),
            $user
        )
            ->where('uuid', $orderItemUuid)
            ->firstOrFail();

        return LaboratoryReferral::query()
            ->with($this->detailRelations())
            ->where('laboratory_order_item_id', $item->getKey())
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
    }

    private function visibleReferralQuery(
        TenantUser $user
    ): Builder {
        return $this->branchAccess->scopeVisible(
            LaboratoryReferral::query(),
            $user
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyReferralFilters(
        Builder $query,
        array $filters
    ): Builder {
        return $query
            ->when(
                $filters['search'] ?? null,
                fn (Builder $query, string $search): Builder => $query
                    ->where(
                        fn (Builder $query): Builder => $query
                            ->where(
                                'referral_number',
                                'like',
                                "%{$search}%"
                            )
                            ->orWhere(
                                'external_reference',
                                'like',
                                "%{$search}%"
                            )
                            ->orWhereHas(
                                'referralLaboratory',
                                fn (Builder $laboratory): Builder => (
                                    $laboratory
                                        ->where(
                                            'name',
                                            'like',
                                            "%{$search}%"
                                        )
                                        ->orWhere(
                                            'code',
                                            'like',
                                            "%{$search}%"
                                        )
                                )
                            )
                            ->orWhereHas(
                                'orderItem.order',
                                fn (Builder $order): Builder => $order
                                    ->where(
                                        'order_number',
                                        'like',
                                        "%{$search}%"
                                    )
                                    ->orWhereHas(
                                        'patient',
                                        fn (Builder $patient): Builder => (
                                            $patient
                                                ->where(
                                                    'first_name',
                                                    'like',
                                                    "%{$search}%"
                                                )
                                                ->orWhere(
                                                    'last_name',
                                                    'like',
                                                    "%{$search}%"
                                                )
                                                ->orWhere(
                                                    'medical_record_number',
                                                    'like',
                                                    "%{$search}%"
                                                )
                                        )
                                    )
                            )
                    )
            )
            ->when(
                $filters['status'] ?? null,
                fn (Builder $query, string $status): Builder => (
                    $query->where('status', $status)
                )
            )
            ->when(
                $filters['laboratory_uuid'] ?? null,
                fn (Builder $query, string $uuid): Builder => (
                    $query->whereHas(
                        'referralLaboratory',
                        fn (Builder $laboratory): Builder => (
                            $laboratory->where('uuid', $uuid)
                        )
                    )
                )
            )
            ->when(
                $filters['branch_uuid'] ?? null,
                fn (Builder $query, string $uuid): Builder => (
                    $query->whereHas(
                        'dispatchBranch',
                        fn (Builder $branch): Builder => (
                            $branch->where('uuid', $uuid)
                        )
                    )
                )
            )
            ->when(
                $filters['order_item_uuid'] ?? null,
                fn (Builder $query, string $uuid): Builder => (
                    $query->whereHas(
                        'orderItem',
                        fn (Builder $item): Builder => (
                            $item->where('uuid', $uuid)
                        )
                    )
                )
            )
            ->when(
                $filters['date_from'] ?? null,
                fn (Builder $query, string $date): Builder => (
                    $query->whereDate('created_at', '>=', $date)
                )
            )
            ->when(
                $filters['date_to'] ?? null,
                fn (Builder $query, string $date): Builder => (
                    $query->whereDate('created_at', '<=', $date)
                )
            )
            ->when(
                array_key_exists('overdue', $filters),
                function (Builder $query) use ($filters): Builder {
                    if ((bool) $filters['overdue']) {
                        return $query
                            ->whereNotIn(
                                'status',
                                LaboratoryReferral::TERMINAL_STATUSES
                            )
                            ->whereNotNull('turnaround_due_at')
                            ->where('turnaround_due_at', '<', now());
                    }

                    return $query->where(
                        fn (Builder $query): Builder => $query
                            ->whereNull('turnaround_due_at')
                            ->orWhere(
                                'turnaround_due_at',
                                '>=',
                                now()
                            )
                            ->orWhereIn(
                                'status',
                                LaboratoryReferral::TERMINAL_STATUSES
                            )
                    );
                }
            );
    }

    /**
     * @return array<int, string>
     */
    private function listRelations(): array
    {
        return [
            'orderItem.order.patient',
            'orderItem.order.collectionBranch',
            'orderItem.order.processingBranch',
            'orderItem.order.priceList',
            'dispatchBranch',
            'creator',
            'referralLaboratory',
            'referralLaboratoryTest.labTest',
            'specimens',
            'latestExternalResult.testResult',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function detailRelations(): array
    {
        return array_merge(
            $this->listRelations(),
            [
                'externalResults' => static fn ($query) => (
                    $query->with([
                        'receiver',
                        'supersededResult',
                        'testResult',
                    ])->orderBy('version')
                ),
                'events' => static fn ($query) => (
                    $query->with('actor')
                        ->orderBy('occurred_at')
                        ->orderBy('id')
                ),
            ]
        );
    }
}
