<?php

namespace App\Services\Tenant\TestResult;

use App\Models\LaboratoryOrderItem;
use App\Models\TestResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TestResultManagementService
{
    /**
     * Relations required by the result resource.
     *
     * @var array<int, string>
     */
    private const RELATIONS = [
        'orderItem.order.patient',
        'enteredBy',
        'submittedBy',
        'reviewedBy',
    ];

    /**
     * Create the single draft result for an order item.
     *
     * @param array<string, mixed> $attributes
     */
    public function createDraft(
        string $orderItemUuid,
        array $attributes,
        int $userId
    ): TestResult {
        return DB::connection('tenant')->transaction(
            function () use (
                $orderItemUuid,
                $attributes,
                $userId
            ): TestResult {
                $item = LaboratoryOrderItem::query()
                    ->with([
                        'order',
                        'specimens',
                    ])
                    ->where('uuid', $orderItemUuid)
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->assertItemAcceptsResult($item);

                $resultExists = TestResult::query()
                    ->where(
                        'laboratory_order_item_id',
                        $item->id
                    )
                    ->exists();

                if ($resultExists) {
                    throw ValidationException::withMessages([
                        'order_item' => [
                            'This laboratory order item already has a test result.',
                        ],
                    ]);
                }

                $result = TestResult::query()->create([
                    'laboratory_order_item_id' => $item->id,
                    'results_data' => $this->normalizeResults(
                        $attributes['results_data'] ?? []
                    ),
                    'notes' => $this->nullableText(
                        $attributes['notes'] ?? null
                    ),
                    'status' => TestResult::STATUS_DRAFT,
                    'entered_by' => $userId,
                ]);

                return $result->load(
                    self::RELATIONS
                );
            },
            3
        );
    }

    /**
     * Update a draft or rejected result.
     *
     * @param array<string, mixed> $attributes
     */
    public function updateDraft(
        string $uuid,
        array $attributes
    ): TestResult {
        return DB::connection('tenant')->transaction(
            function () use (
                $uuid,
                $attributes
            ): TestResult {
                $result = TestResult::query()
                    ->where('uuid', $uuid)
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->assertResultIsEditable($result);

                $updates = [];

                if (
                    array_key_exists(
                        'results_data',
                        $attributes
                    )
                ) {
                    $updates['results_data'] = (
                        $this->normalizeResults(
                            $attributes['results_data']
                        )
                    );
                }

                if (
                    array_key_exists(
                        'notes',
                        $attributes
                    )
                ) {
                    $updates['notes'] = (
                        $this->nullableText(
                            $attributes['notes']
                        )
                    );
                }

                if (
                    $result->status
                        === TestResult::STATUS_REJECTED
                ) {
                    $updates = array_merge(
                        $updates,
                        [
                            'status' => (
                                TestResult::STATUS_DRAFT
                            ),
                            'submitted_by' => null,
                            'submitted_at' => null,
                            'reviewed_by' => null,
                            'reviewed_at' => null,
                            'rejection_reason' => null,
                        ]
                    );
                }

                if ($updates !== []) {
                    $result->forceFill($updates)->save();
                }

                return $result
                    ->fresh()
                    ->load(self::RELATIONS);
            },
            3
        );
    }

    /**
     * Require the order item to be ready for entry.
     */
    private function assertItemAcceptsResult(
        LaboratoryOrderItem $item
    ): void {
        if ($item->order?->status !== 'in_progress') {
            throw ValidationException::withMessages([
                'order_item' => [
                    'The laboratory order must be in progress before results can be entered.',
                ],
            ]);
        }

        if ($item->status !== 'in_progress') {
            throw ValidationException::withMessages([
                'order_item' => [
                    'Start processing this laboratory order item before entering a result.',
                ],
            ]);
        }

        $hasReceivedSpecimen = $item->specimens
            ->contains(
                fn ($specimen): bool => (
                    $specimen->status === 'received'
                )
            );

        if (! $hasReceivedSpecimen) {
            throw ValidationException::withMessages([
                'order_item' => [
                    'A received specimen is required before entering a result.',
                ],
            ]);
        }
    }

    /**
     * Restrict editing to draft and rejected results.
     */
    private function assertResultIsEditable(
        TestResult $result
    ): void {
        if (
            in_array(
                $result->status,
                [
                    TestResult::STATUS_DRAFT,
                    TestResult::STATUS_REJECTED,
                ],
                true
            )
        ) {
            return;
        }

        throw ValidationException::withMessages([
            'test_result' => [
                sprintf(
                    'A %s test result cannot be edited.',
                    $result->status
                ),
            ],
        ]);
    }

    /**
     * Normalize submitted parameter results.
     *
     * @param array<int, array<string, mixed>> $results
     * @return array<int, array<string, mixed>>
     */
    private function normalizeResults(
        array $results
    ): array {
        return array_values(
            array_map(
                fn (array $result): array => [
                    'name' => trim(
                        (string) $result['name']
                    ),
                    'value' => $this->nullableText(
                        $result['value'] ?? null
                    ),
                    'unit' => $this->nullableText(
                        $result['unit'] ?? null
                    ),
                    'reference_range' => (
                        $this->nullableText(
                            $result['reference_range']
                                ?? null
                        )
                    ),
                    'flag' => $this->nullableText(
                        $result['flag'] ?? null
                    ),
                    'notes' => $this->nullableText(
                        $result['notes'] ?? null
                    ),
                ],
                $results
            )
        );
    }

    /**
     * Normalize optional text.
     */
    private function nullableText(
        mixed $value
    ): ?string {
        if ($value === null) {
            return null;
        }

        $normalized = trim((string) $value);

        return $normalized === ''
            ? null
            : $normalized;
    }
}
