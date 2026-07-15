<?php

namespace App\Services\Tenant\LaboratoryOrder;

use App\Models\LabTest;
use App\Models\LaboratoryOrder;
use App\Models\Patient;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class LaboratoryOrderCreationService
{
    /**
     * Create a complete order and its catalogue snapshots.
     *
     * @param array{
     *     patient_id: int,
     *     referring_clinician?: string|null,
     *     priority: string,
     *     clinical_notes?: string|null,
     *     items: array<int, array{lab_test_id: int}>
     * } $data
     */
    public function create(array $data): LaboratoryOrder
    {
        return DB::connection('tenant')->transaction(
            function () use ($data): LaboratoryOrder {
                $userId = Auth::id();

                if ($userId === null) {
                    throw new RuntimeException(
                        'Authenticated tenant user was not found.'
                    );
                }

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

                $labTestIds = collect($data['items'])
                    ->pluck('lab_test_id')
                    ->map(
                        static fn (mixed $id): int => (int) $id
                    )
                    ->values();

                $labTests = LabTest::query()
                    ->whereIn('id', $labTestIds)
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy(
                        static fn (LabTest $labTest): int => (
                            (int) $labTest->id
                        )
                    );

                $this->validateLabTests(
                    $data['items'],
                    $labTests
                );

                $order = LaboratoryOrder::query()->create([
                    'order_number' => $this->generateOrderNumber(),
                    'patient_id' => $patient->id,
                    'ordered_by' => $userId,
                    'referring_clinician' => (
                        $data['referring_clinician']
                        ?? null
                    ),
                    'priority' => $data['priority'],
                    'status' => 'pending',
                    'clinical_notes' => (
                        $data['clinical_notes']
                        ?? null
                    ),
                    'total_amount' => '0.00',
                    'ordered_at' => now(),
                ]);

                $totalMinorUnits = 0;

                foreach ($data['items'] as $item) {
                    $labTestId = (int) $item['lab_test_id'];

                    /** @var LabTest $labTest */
                    $labTest = $labTests->get($labTestId);

                    $price = (string) (
                        $labTest->price
                        ?? '0.00'
                    );

                    $totalMinorUnits += $this->toMinorUnits(
                        $price
                    );

                    $order->items()->create([
                        'lab_test_id' => $labTest->id,
                        'test_name_snapshot' => $labTest->name,
                        'test_category_snapshot' => (
                            $labTest->category
                        ),
                        'price_snapshot' => $price,
                        'turnaround_time_hours_snapshot' => (
                            $labTest->turnaround_time_hours
                        ),
                        'parameters_snapshot' => (
                            $labTest->parameters
                        ),
                        'status' => 'pending',
                    ]);
                }

                $order->update([
                    'total_amount' => $this->formatMinorUnits(
                        $totalMinorUnits
                    ),
                ]);

                return $order
                    ->fresh()
                    ->load([
                        'patient',
                        'orderedBy',
                        'items',
                    ]);
            }
        );
    }

    /**
     * Confirm every requested test belongs to the
     * current tenant and is active.
     *
     * @param array<int, array{lab_test_id: int}> $items
     * @param Collection<int, LabTest> $labTests
     */
    private function validateLabTests(
        array $items,
        Collection $labTests
    ): void {
        $errors = [];

        foreach ($items as $index => $item) {
            $labTestId = (int) $item['lab_test_id'];

            if (! $labTests->has($labTestId)) {
                $errors[
                    "items.{$index}.lab_test_id"
                ] = [
                    'The selected laboratory test is unavailable or inactive.',
                ];
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages(
                $errors
            );
        }
    }

    /**
     * Generate a human-readable unique order number.
     */
    private function generateOrderNumber(): string
    {
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $suffix = strtoupper(
                substr(
                    str_replace(
                        '-',
                        '',
                        (string) Str::uuid()
                    ),
                    0,
                    8
                )
            );

            $orderNumber = sprintf(
                'LAB-%s-%s',
                now()->format('Ymd'),
                $suffix
            );

            $exists = LaboratoryOrder::query()
                ->where(
                    'order_number',
                    $orderNumber
                )
                ->exists();

            if (! $exists) {
                return $orderNumber;
            }
        }

        throw new RuntimeException(
            'Unable to generate a unique laboratory order number.'
        );
    }

    /**
     * Convert a decimal amount to minor units.
     */
    private function toMinorUnits(string $amount): int
    {
        $normalized = number_format(
            (float) $amount,
            2,
            '.',
            ''
        );

        [$whole, $fraction] = explode(
            '.',
            $normalized
        );

        return ((int) $whole * 100)
            + (int) $fraction;
    }

    /**
     * Convert minor units back to decimal format.
     */
    private function formatMinorUnits(int $amount): string
    {
        return sprintf(
            '%d.%02d',
            intdiv($amount, 100),
            $amount % 100
        );
    }
}
