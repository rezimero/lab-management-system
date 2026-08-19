<?php

namespace App\Services\Tenant\Analyzer;

use App\Models\AnalyzerResultImport;
use App\Models\LaboratoryOrderItem;
use App\Models\LaboratoryReferral;
use App\Models\Specimen;
use App\Models\TestResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AnalyzerDraftImportService
{
    public function __construct(
        private readonly AnalyzerImportEventService $events
    ) {}

    public function create(
        AnalyzerResultImport $candidate
    ): TestResult {
        return DB::connection('tenant')->transaction(
            function () use ($candidate): TestResult {
                $import = AnalyzerResultImport::query()
                    ->with(['instrument', 'testMapping'])
                    ->whereKey($candidate->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                if (
                    $import->status
                    !== AnalyzerResultImport::STATUS_READY
                ) {
                    throw ValidationException::withMessages([
                        'analyzer_import' => [
                            'Only a ready analyzer result can create a draft.',
                        ],
                    ]);
                }

                if (
                    $import->laboratory_order_item_id === null
                    || $import->specimen_id === null
                ) {
                    throw ValidationException::withMessages([
                        'analyzer_import' => [
                            'The analyzer result is not fully matched.',
                        ],
                    ]);
                }

                $item = LaboratoryOrderItem::query()
                    ->with('order')
                    ->whereKey($import->laboratory_order_item_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $specimen = Specimen::query()
                    ->whereKey($import->specimen_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (
                    $import->testMapping === null
                    || ! $import->testMapping->is_active
                    || (int) $item->lab_test_id
                        !== (int) $import->testMapping->lab_test_id
                ) {
                    throw ValidationException::withMessages([
                        'analyzer_import' => [
                            'The analyzer test mapping changed before draft creation.',
                        ],
                    ]);
                }

                $specimenStillLinked = $item->specimens()
                    ->whereKey($specimen->getKey())
                    ->exists();

                if (! $specimenStillLinked) {
                    throw ValidationException::withMessages([
                        'analyzer_import' => [
                            'The matched specimen is no longer linked to the order item.',
                        ],
                    ]);
                }

                if (
                    $item->order?->status !== 'in_progress'
                    || $item->status !== 'in_progress'
                    || $specimen->status !== 'received'
                ) {
                    throw ValidationException::withMessages([
                        'analyzer_import' => [
                            'The laboratory workflow changed before analyzer draft creation.',
                        ],
                    ]);
                }

                if (
                    (int) $item->order?->processing_branch_id
                    !== (int) $import->instrument?->branch_id
                ) {
                    throw ValidationException::withMessages([
                        'analyzer_import' => [
                            'The analyzer and order processing branches no longer match.',
                        ],
                    ]);
                }

                if (
                    LaboratoryReferral::query()
                        ->where(
                            'laboratory_order_item_id',
                            $item->getKey()
                        )
                        ->open()
                        ->exists()
                ) {
                    throw ValidationException::withMessages([
                        'analyzer_import' => [
                            'The order item now has an active laboratory referral.',
                        ],
                    ]);
                }

                if (
                    TestResult::query()
                        ->where(
                            'laboratory_order_item_id',
                            $item->getKey()
                        )
                        ->exists()
                ) {
                    throw ValidationException::withMessages([
                        'analyzer_import' => [
                            'The order item already has a test result.',
                        ],
                    ]);
                }

                $result = TestResult::query()->create([
                    'laboratory_order_item_id' => $item->getKey(),
                    'results_data' => $import->normalized_results ?? [],
                    'notes' => null,
                    'status' => TestResult::STATUS_DRAFT,
                    'entered_by' => null,
                ]);

                $fromStatus = $import->status;

                $import->forceFill([
                    'test_result_id' => $result->getKey(),
                    'status' => AnalyzerResultImport::STATUS_IMPORTED,
                    'last_processed_at' => now(),
                ])->save();

                $this->events->machine(
                    $import,
                    'draft_imported',
                    $fromStatus,
                    AnalyzerResultImport::STATUS_IMPORTED,
                    [
                        'test_result_uuid' => $result->uuid,
                    ]
                );

                return $result;
            },
            3
        );
    }
}
