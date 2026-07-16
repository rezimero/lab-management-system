<?php

namespace App\Services\Tenant\Specimen;

use App\Models\Specimen;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SpecimenReplacementService
{
    /**
     * Collect a replacement for a rejected specimen.
     *
     * @param array<string, mixed> $payload
     */
    public function replace(
        string $sourceUuid,
        array $payload,
        ?int $collectorId
    ): Specimen {
        return DB::connection('tenant')
            ->transaction(
                function () use (
                    $sourceUuid,
                    $payload,
                    $collectorId
                ): Specimen {
                    $source = Specimen::query()
                        ->with([
                            'order',
                            'orderItems',
                        ])
                        ->where('uuid', $sourceUuid)
                        ->lockForUpdate()
                        ->firstOrFail();

                    $this->assertRejected($source);
                    $this->assertOrderAwaitingProcessing(
                        $source
                    );
                    $this->assertNotAlreadyReplaced(
                        $source
                    );

                    $itemIds = $source->orderItems
                        ->where(
                            'status',
                            '!=',
                            'cancelled'
                        )
                        ->modelKeys();

                    if ($itemIds === []) {
                        throw ValidationException::withMessages([
                            'specimen' => [
                                'The rejected specimen has no active order items to replace.',
                            ],
                        ]);
                    }

                    $replacement = Specimen::query()
                        ->create([
                            'laboratory_order_id' => (
                                $source->laboratory_order_id
                            ),
                            'replaces_specimen_id' => (
                                $source->getKey()
                            ),
                            'accession_number' => (
                                $this->generateAccessionNumber()
                            ),
                            'specimen_type' => (
                                $this->specimenType(
                                    $source,
                                    $payload
                                )
                            ),
                            'container_type' => (
                                $this->value(
                                    $payload,
                                    'container_type',
                                    $source->container_type
                                )
                            ),
                            'collection_site' => (
                                $this->value(
                                    $payload,
                                    'collection_site',
                                    $source->collection_site
                                )
                            ),
                            'volume' => $this->value(
                                $payload,
                                'volume',
                                $source->volume
                            ),
                            'volume_unit' => (
                                $this->value(
                                    $payload,
                                    'volume_unit',
                                    $source->volume_unit
                                )
                            ),
                            'status' => 'collected',
                            'collected_by' => $collectorId,
                            'collected_at' => isset(
                                $payload['collected_at']
                            )
                                ? Carbon::parse(
                                    $payload['collected_at']
                                )
                                : now(),
                            'received_at' => null,
                            'rejected_at' => null,
                            'rejection_reason' => null,
                            'notes' => (
                                $payload['notes'] ?? null
                            ),
                        ]);

                    $replacement
                        ->orderItems()
                        ->attach($itemIds);

                    return $replacement
                        ->fresh()
                        ->load([
                            'order.patient',
                            'collectedBy',
                            'orderItems',
                            'replaces',
                            'replacement',
                        ]);
                },
                3
            );
    }

    /**
     * Ensure only rejected specimens are replaced.
     */
    private function assertRejected(
        Specimen $source
    ): void {
        if ($source->status === 'rejected') {
            return;
        }

        throw ValidationException::withMessages([
            'specimen' => [
                'Only a rejected specimen can be replaced.',
            ],
        ]);
    }

    /**
     * Ensure replacement occurs before processing begins.
     */
    private function assertOrderAwaitingProcessing(
        Specimen $source
    ): void {
        if (
            $source->order !== null
            && $source->order->status
                === 'sample_collected'
        ) {
            return;
        }

        throw ValidationException::withMessages([
            'specimen' => [
                'A specimen can only be replaced while its order is awaiting processing.',
            ],
        ]);
    }

    /**
     * Prevent multiple direct replacements.
     */
    private function assertNotAlreadyReplaced(
        Specimen $source
    ): void {
        $exists = Specimen::query()
            ->where(
                'replaces_specimen_id',
                $source->getKey()
            )
            ->exists();

        if (! $exists) {
            return;
        }

        throw ValidationException::withMessages([
            'specimen' => [
                'This rejected specimen already has a replacement.',
            ],
        ]);
    }

    /**
     * Resolve the replacement specimen type.
     *
     * @param array<string, mixed> $payload
     */
    private function specimenType(
        Specimen $source,
        array $payload
    ): string {
        $type = trim(
            (string) (
                $payload['specimen_type']
                ?? $source->specimen_type
            )
        );

        if ($type !== '') {
            return $type;
        }

        throw ValidationException::withMessages([
            'specimen_type' => [
                'The replacement specimen type is required.',
            ],
        ]);
    }

    /**
     * Return an explicitly submitted value or its fallback.
     *
     * @param array<string, mixed> $payload
     */
    private function value(
        array $payload,
        string $key,
        mixed $fallback
    ): mixed {
        return array_key_exists($key, $payload)
            ? $payload[$key]
            : $fallback;
    }

    /**
     * Generate a tenant-unique accession number.
     */
    private function generateAccessionNumber(): string
    {
        do {
            $accessionNumber = sprintf(
                'SP-%s-%s',
                now()->format('Ymd'),
                strtoupper(
                    Str::random(8)
                )
            );
        } while (
            Specimen::query()
                ->where(
                    'accession_number',
                    $accessionNumber
                )
                ->exists()
        );

        return $accessionNumber;
    }
}
