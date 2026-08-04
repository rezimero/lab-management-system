<?php

namespace App\Services\Tenant\Referral;

use App\Models\LaboratoryOrderItem;
use App\Models\LaboratoryReferral;
use App\Models\LaboratoryReferralEvent;
use App\Models\ReferralLaboratoryTest;
use App\Models\Specimen;
use App\Models\Tenant\User as TenantUser;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReferralCreationService
{
    private const RELATIONS = [
        'orderItem.order.patient',
        'orderItem.order.collectionBranch',
        'orderItem.order.processingBranch',
        'orderItem.order.priceList',
        'dispatchBranch',
        'referralLaboratory',
        'referralLaboratoryTest.labTest',
        'specimens',
        'events.actor',
    ];

    public function __construct(
        protected ReferralNumberService $numbers,
        protected ReferralBranchAccessService $branchAccess,
        protected ReferralActorSnapshotFactory $actorSnapshots
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(
        array $data,
        TenantUser $actor
    ): LaboratoryReferral {
        return DB::connection('tenant')->transaction(
            function () use ($data, $actor): LaboratoryReferral {
                $item = LaboratoryOrderItem::query()
                    ->with([
                        'order.patient',
                        'order.collectionBranch',
                        'order.processingBranch',
                        'order.priceList',
                        'specimens',
                        'testResult',
                    ])
                    ->where('uuid', $data['order_item_uuid'])
                    ->lockForUpdate()
                    ->first();

                if ($item === null) {
                    throw ValidationException::withMessages([
                        'order_item_uuid' => [
                            'The selected laboratory order item does not exist.',
                        ],
                    ]);
                }

                $this->assertEligibleItem($item);

                $processingBranch = $item->order?->processingBranch;

                if ($processingBranch === null) {
                    throw ValidationException::withMessages([
                        'order_item_uuid' => [
                            'The laboratory order does not have a processing branch.',
                        ],
                    ]);
                }

                $this->branchAccess->authorizeMutation(
                    $actor,
                    $processingBranch
                );

                $mapping = ReferralLaboratoryTest::query()
                    ->with(['referralLaboratory', 'labTest'])
                    ->where(
                        'uuid',
                        $data['referral_laboratory_test_uuid']
                    )
                    ->lockForUpdate()
                    ->first();

                if ($mapping === null) {
                    throw ValidationException::withMessages([
                        'referral_laboratory_test_uuid' => [
                            'The selected referral test mapping does not exist.',
                        ],
                    ]);
                }

                $this->assertEligibleMapping($item, $mapping);

                $hasOpenReferral = LaboratoryReferral::query()
                    ->where('open_order_item_key', $item->uuid)
                    ->lockForUpdate()
                    ->exists();

                if ($hasOpenReferral) {
                    throw ValidationException::withMessages([
                        'order_item_uuid' => [
                            'This order item already has an open referral.',
                        ],
                    ]);
                }

                $specimens = $this->selectedSpecimens(
                    $item,
                    $data['specimen_uuids']
                );
                $actorSnapshot = $this->actorSnapshots->make($actor);
                $laboratory = $mapping->referralLaboratory;
                $turnaroundHours = $mapping->turnaround_hours
                    ?? $laboratory->default_turnaround_hours;
                $quotedCost = array_key_exists('quoted_cost', $data)
                    ? $data['quoted_cost']
                    : $mapping->cost;

                $referral = LaboratoryReferral::query()->create([
                    'referral_number' => $this->numbers->generate(),
                    'laboratory_order_item_id' => $item->getKey(),
                    'referral_laboratory_id' => $laboratory->getKey(),
                    'referral_laboratory_test_id' => $mapping->getKey(),
                    'dispatch_branch_id' => $processingBranch->getKey(),
                    'open_order_item_key' => $item->uuid,
                    'status' => LaboratoryReferral::STATUS_DRAFT,
                    'laboratory_snapshot' => $this->laboratorySnapshot(
                        $laboratory
                    ),
                    'test_snapshot' => $this->testSnapshot(
                        $item,
                        $mapping,
                        $turnaroundHours
                    ),
                    'quoted_cost' => $quotedCost,
                    'currency' => $mapping->currency,
                    'external_reference' => $this->nullableText(
                        $data['external_reference'] ?? null
                    ),
                    'dispatch_notes' => $this->nullableText(
                        $data['dispatch_notes'] ?? null
                    ),
                    'created_by' => $actor->getKey(),
                ]);

                foreach ($specimens as $specimen) {
                    $referral->specimens()->attach(
                        $specimen->getKey(),
                        [
                            'specimen_snapshot' => json_encode(
                                $this->specimenSnapshot($specimen),
                                JSON_THROW_ON_ERROR
                            ),
                            'linked_by' => $actor->getKey(),
                        ]
                    );
                }

                LaboratoryReferralEvent::query()->create([
                    'laboratory_referral_id' => $referral->getKey(),
                    'event_type' => 'created',
                    'from_status' => null,
                    'to_status' => LaboratoryReferral::STATUS_DRAFT,
                    'actor_id' => $actor->getKey(),
                    'actor_snapshot' => $actorSnapshot,
                    'payload' => [
                        'order_item_uuid' => $item->uuid,
                        'dispatch_branch_uuid' => $processingBranch->uuid,
                        'referral_laboratory_test_uuid' => $mapping->uuid,
                        'specimen_uuids' => $specimens
                            ->pluck('uuid')
                            ->values()
                            ->all(),
                        'quoted_cost' => number_format(
                            (float) $quotedCost,
                            2,
                            '.',
                            ''
                        ),
                        'currency' => $mapping->currency,
                    ],
                    'occurred_at' => now(),
                    'created_at' => now(),
                ]);

                return $referral->fresh(self::RELATIONS);
            },
            3
        );
    }

    private function assertEligibleItem(
        LaboratoryOrderItem $item
    ): void {
        if ($item->order?->status !== 'in_progress') {
            throw ValidationException::withMessages([
                'order_item_uuid' => [
                    'The laboratory order must be in progress before referral.',
                ],
            ]);
        }

        if ($item->status !== 'in_progress') {
            throw ValidationException::withMessages([
                'order_item_uuid' => [
                    'Start processing the order item before referral.',
                ],
            ]);
        }

        if ($item->testResult !== null) {
            throw ValidationException::withMessages([
                'order_item_uuid' => [
                    'An order item with a test result cannot be referred.',
                ],
            ]);
        }
    }

    private function assertEligibleMapping(
        LaboratoryOrderItem $item,
        ReferralLaboratoryTest $mapping
    ): void {
        if (! $mapping->is_active) {
            throw ValidationException::withMessages([
                'referral_laboratory_test_uuid' => [
                    'Select an active referral test mapping.',
                ],
            ]);
        }

        if (! $mapping->referralLaboratory?->is_active) {
            throw ValidationException::withMessages([
                'referral_laboratory_test_uuid' => [
                    'Select an active referral laboratory.',
                ],
            ]);
        }

        if (! $mapping->labTest?->is_active) {
            throw ValidationException::withMessages([
                'referral_laboratory_test_uuid' => [
                    'The mapped local laboratory test is inactive.',
                ],
            ]);
        }

        if (
            (int) $mapping->lab_test_id
            !== (int) $item->lab_test_id
        ) {
            throw ValidationException::withMessages([
                'referral_laboratory_test_uuid' => [
                    'The selected mapping does not belong to this order item test.',
                ],
            ]);
        }
    }

    /**
     * @param  array<int, string>  $uuids
     * @return Collection<int, Specimen>
     */
    private function selectedSpecimens(
        LaboratoryOrderItem $item,
        array $uuids
    ): Collection {
        $selected = $item->specimens
            ->whereIn('uuid', $uuids)
            ->values();

        if ($selected->count() !== count($uuids)) {
            throw ValidationException::withMessages([
                'specimen_uuids' => [
                    'Every selected specimen must be linked to the order item.',
                ],
            ]);
        }

        foreach ($selected as $specimen) {
            if (
                (int) $specimen->laboratory_order_id
                !== (int) $item->laboratory_order_id
            ) {
                throw ValidationException::withMessages([
                    'specimen_uuids' => [
                        'Every selected specimen must belong to the same order.',
                    ],
                ]);
            }

            if ($specimen->status !== 'received') {
                throw ValidationException::withMessages([
                    'specimen_uuids' => [
                        'Only received specimens may be referred.',
                    ],
                ]);
            }
        }

        return $selected;
    }

    /**
     * @return array<string, mixed>
     */
    private function laboratorySnapshot(mixed $laboratory): array
    {
        return [
            'uuid' => $laboratory->uuid,
            'code' => $laboratory->code,
            'name' => $laboratory->name,
            'accreditation_number' => $laboratory->accreditation_number,
            'contact_name' => $laboratory->contact_name,
            'email' => $laboratory->email,
            'phone' => $laboratory->phone,
            'address_line_1' => $laboratory->address_line_1,
            'address_line_2' => $laboratory->address_line_2,
            'city' => $laboratory->city,
            'state' => $laboratory->state,
            'country' => $laboratory->country,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function testSnapshot(
        LaboratoryOrderItem $item,
        ReferralLaboratoryTest $mapping,
        ?int $turnaroundHours
    ): array {
        return [
            'uuid' => $mapping->uuid,
            'lab_test_uuid' => $mapping->labTest?->uuid,
            'local_test_name' => $item->test_name_snapshot,
            'local_test_category' => $item->test_category_snapshot,
            'external_test_code' => $mapping->external_test_code,
            'external_test_name' => $mapping->external_test_name,
            'cost' => $mapping->cost,
            'currency' => $mapping->currency,
            'turnaround_hours' => $turnaroundHours,
            'specimen_requirements' => $mapping->specimen_requirements,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function specimenSnapshot(Specimen $specimen): array
    {
        return [
            'uuid' => $specimen->uuid,
            'accession_number' => $specimen->accession_number,
            'specimen_type' => $specimen->specimen_type,
            'container_type' => $specimen->container_type,
            'collection_site' => $specimen->collection_site,
            'volume' => $specimen->volume,
            'volume_unit' => $specimen->volume_unit,
            'status' => $specimen->status,
            'collected_at' => $specimen->collected_at?->toISOString(),
            'received_at' => $specimen->received_at?->toISOString(),
            'notes' => $specimen->notes,
        ];
    }

    private function nullableText(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
