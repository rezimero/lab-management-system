<?php

namespace App\Services\Tenant\LaboratoryOrder;

use App\Models\LaboratoryOrder;
use App\Models\LabTest;
use App\Models\Patient;
use App\Models\ReferringClinician;
use App\Models\Tenant\User as TenantUser;
use App\Services\Tenant\Billing\PriceListService;
use App\Services\Tenant\Branch\BranchSelectionService;
use App\Services\Tenant\Portal\PortalAuditLogger;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class LaboratoryOrderCreationService
{
    public function __construct(
        protected BranchSelectionService $branches,
        protected PriceListService $priceLists,
        protected PortalAuditLogger $portalAudit,
    ) {}

    /**
     * @param array{
     *     patient_id: int,
     *     collection_branch_uuid?: string|null,
     *     processing_branch_uuid?: string|null,
     *     price_list_uuid?: string|null,
     *     referring_clinician_uuid?: string|null,
     *     referring_clinician?: string|null,
     *     priority: string,
     *     clinical_notes?: string|null,
     *     items: array<int, array{lab_test_id: int}>
     * } $data
     */
    public function create(
        array $data,
        TenantUser $user
    ): LaboratoryOrder {
        return DB::connection('tenant')->transaction(
            function () use ($data, $user): LaboratoryOrder {
                $patient = Patient::query()
                    ->whereKey($data['patient_id'])
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->first();

                if ($patient === null) {
                    throw ValidationException::withMessages([
                        'patient_id' => [
                            'The selected patient is unavailable or inactive.',
                        ],
                    ]);
                }

                $referringClinician = $this
                    ->resolveReferringClinician(
                        $data['referring_clinician_uuid'] ?? null
                    );

                $labTestIds = collect($data['items'])
                    ->pluck('lab_test_id')
                    ->map(static fn (mixed $id): int => (int) $id)
                    ->values();

                $labTests = LabTest::query()
                    ->whereIn('id', $labTestIds)
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy(static fn (LabTest $labTest): int => (int) $labTest->id);

                $this->validateLabTests($data['items'], $labTests);

                $branchSelection = $this->branches->resolve(
                    $user,
                    $data['collection_branch_uuid'] ?? null,
                    $data['processing_branch_uuid'] ?? null
                );

                $explicitPriceList = filled($data['price_list_uuid'] ?? null);
                $priceList = $this->priceLists->effective(
                    $data['price_list_uuid'] ?? null
                );

                if ($explicitPriceList && $priceList === null) {
                    throw ValidationException::withMessages([
                        'price_list_uuid' => [
                            'The selected price list is unavailable or not currently effective.',
                        ],
                    ]);
                }

                $priceItems = $priceList?->items
                    ->keyBy('lab_test_id')
                    ?? collect();

                $missingPriceIndexes = [];
                $resolvedPrices = [];

                foreach ($data['items'] as $index => $item) {
                    $labTestId = (int) $item['lab_test_id'];
                    $labTest = $labTests->get($labTestId);
                    $priceItem = $priceItems->get($labTestId);

                    if ($explicitPriceList && $priceItem === null) {
                        $missingPriceIndexes[] = $index;

                        continue;
                    }

                    $resolvedPrices[$labTestId] = (string) (
                        $priceItem?->price
                        ?? $labTest->price
                        ?? '0.00'
                    );
                }

                if ($missingPriceIndexes !== []) {
                    $errors = [];

                    foreach ($missingPriceIndexes as $index) {
                        $errors["items.{$index}.lab_test_id"] = [
                            'The selected price list does not contain this laboratory test.',
                        ];
                    }

                    throw ValidationException::withMessages($errors);
                }

                $order = LaboratoryOrder::query()->create([
                    'order_number' => $this->generateOrderNumber(),
                    'patient_id' => $patient->getKey(),
                    'ordered_by' => $user->getKey(),
                    'collection_branch_id' => $branchSelection['collection']->getKey(),
                    'processing_branch_id' => $branchSelection['processing']->getKey(),
                    'price_list_id' => $priceList?->getKey(),
                    'referring_clinician_id' => (
                        $referringClinician?->getKey()
                    ),
                    'referring_clinician' => (
                        $referringClinician?->name
                        ?? ($data['referring_clinician'] ?? null)
                    ),
                    'priority' => $data['priority'],
                    'status' => 'pending',
                    'clinical_notes' => $data['clinical_notes'] ?? null,
                    'total_amount' => '0.00',
                    'ordered_at' => now(),
                ]);

                $totalMinorUnits = 0;

                foreach ($data['items'] as $item) {
                    $labTestId = (int) $item['lab_test_id'];
                    $labTest = $labTests->get($labTestId);
                    $price = $resolvedPrices[$labTestId];
                    $totalMinorUnits += $this->toMinorUnits($price);

                    $order->items()->create([
                        'lab_test_id' => $labTest->getKey(),
                        'test_name_snapshot' => $labTest->name,
                        'test_category_snapshot' => $labTest->category,
                        'price_snapshot' => $price,
                        'turnaround_time_hours_snapshot' => $labTest->turnaround_time_hours,
                        'parameters_snapshot' => $labTest->parameters,
                        'status' => 'pending',
                    ]);
                }

                $order->update([
                    'total_amount' => $this->formatMinorUnits($totalMinorUnits),
                ]);

                if ($referringClinician !== null) {
                    $this->portalAudit->tenantUser(
                        'clinician_order_linked',
                        $user,
                        'laboratory_order',
                        $order,
                        [
                            'referring_clinician_uuid' => (
                                $referringClinician->uuid
                            ),
                        ]
                    );
                }

                return $order->fresh()->load([
                    'patient',
                    'orderedBy',
                    'collectionBranch',
                    'processingBranch',
                    'priceList',
                    'referringClinician',
                    'items',
                ]);
            }
        );
    }

    private function resolveReferringClinician(
        ?string $uuid
    ): ?ReferringClinician {
        if (blank($uuid)) {
            return null;
        }

        $clinician = ReferringClinician::query()
            ->where('uuid', $uuid)
            ->where('is_active', true)
            ->lockForUpdate()
            ->first();

        if ($clinician === null) {
            throw ValidationException::withMessages([
                'referring_clinician_uuid' => [
                    'The selected referring clinician is unavailable or inactive.',
                ],
            ]);
        }

        return $clinician;
    }

    /**
     * @param  array<int, array{lab_test_id: int}>  $items
     * @param  Collection<int, LabTest>  $labTests
     */
    private function validateLabTests(
        array $items,
        Collection $labTests
    ): void {
        $errors = [];

        foreach ($items as $index => $item) {
            if (! $labTests->has((int) $item['lab_test_id'])) {
                $errors["items.{$index}.lab_test_id"] = [
                    'The selected laboratory test is unavailable or inactive.',
                ];
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function generateOrderNumber(): string
    {
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $suffix = strtoupper(substr(
                str_replace('-', '', (string) Str::uuid()),
                0,
                8
            ));
            $orderNumber = sprintf(
                'LAB-%s-%s',
                now()->format('Ymd'),
                $suffix
            );

            if (! LaboratoryOrder::query()
                ->where('order_number', $orderNumber)
                ->exists()) {
                return $orderNumber;
            }
        }

        throw new RuntimeException(
            'Unable to generate a unique laboratory order number.'
        );
    }

    private function toMinorUnits(string $amount): int
    {
        $normalized = number_format((float) $amount, 2, '.', '');
        [$whole, $fraction] = explode('.', $normalized);

        return ((int) $whole * 100) + (int) $fraction;
    }

    private function formatMinorUnits(int $amount): string
    {
        return sprintf('%d.%02d', intdiv($amount, 100), $amount % 100);
    }
}
