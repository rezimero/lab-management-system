<?php

namespace App\Services\Tenant\Analyzer;

use App\Jobs\Tenant\Analyzer\ProcessAnalyzerMessage;
use App\Models\AnalyzerResultImport;
use App\Models\Tenant;
use App\Models\Tenant\User as TenantUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AnalyzerImportReviewService
{
    public function __construct(
        private readonly AnalyzerImportEventService $events
    ) {}

    public function reprocess(
        AnalyzerResultImport $candidate,
        TenantUser $actor
    ): AnalyzerResultImport {
        $tenant = Tenant::current();

        if (! $tenant instanceof Tenant) {
            abort(422, 'Tenant context is required.');
        }

        $import = DB::connection('tenant')->transaction(
            function () use (
                $candidate,
                $actor
            ): AnalyzerResultImport {
                $import = AnalyzerResultImport::query()
                    ->whereKey($candidate->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                if (
                    ! in_array(
                        $import->status,
                        [
                            AnalyzerResultImport::STATUS_NEEDS_REVIEW,
                            AnalyzerResultImport::STATUS_FAILED,
                        ],
                        true
                    )
                ) {
                    throw ValidationException::withMessages([
                        'analyzer_import' => [
                            'Only a review-required or failed analyzer import can be reprocessed.',
                        ],
                    ]);
                }

                if ($import->test_result_id !== null) {
                    throw ValidationException::withMessages([
                        'analyzer_import' => [
                            'An analyzer import already linked to a test result cannot be reprocessed.',
                        ],
                    ]);
                }

                $fromStatus = $import->status;

                $import->forceFill([
                    'status' => AnalyzerResultImport::STATUS_RECEIVED,
                    'validation_issues' => null,
                    'review_note' => null,
                    'rejected_by' => null,
                    'rejected_at' => null,
                ])->save();

                $this->events->user(
                    $import,
                    'reprocess_requested',
                    $fromStatus,
                    AnalyzerResultImport::STATUS_RECEIVED,
                    $actor
                );

                return $import->refresh();
            },
            3
        );

        ProcessAnalyzerMessage::dispatch(
            (int) $tenant->getKey(),
            (int) $import->analyzer_message_id
        );

        return $import->fresh([
            'instrument',
            'message',
            'specimen',
            'orderItem.order',
            'testResult',
            'events' => fn ($query) => $query
                ->orderBy('occurred_at'),
        ]);
    }

    public function reject(
        AnalyzerResultImport $candidate,
        TenantUser $actor,
        string $note
    ): AnalyzerResultImport {
        return DB::connection('tenant')->transaction(
            function () use (
                $candidate,
                $actor,
                $note
            ): AnalyzerResultImport {
                $import = AnalyzerResultImport::query()
                    ->whereKey($candidate->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                if (
                    ! in_array(
                        $import->status,
                        [
                            AnalyzerResultImport::STATUS_NEEDS_REVIEW,
                            AnalyzerResultImport::STATUS_FAILED,
                        ],
                        true
                    )
                ) {
                    throw ValidationException::withMessages([
                        'analyzer_import' => [
                            'Only a review-required or failed analyzer import can be rejected.',
                        ],
                    ]);
                }

                if ($import->test_result_id !== null) {
                    throw ValidationException::withMessages([
                        'analyzer_import' => [
                            'An analyzer import linked to a test result cannot be rejected.',
                        ],
                    ]);
                }

                $fromStatus = $import->status;

                $import->forceFill([
                    'status' => AnalyzerResultImport::STATUS_REJECTED,
                    'rejected_by' => $actor->getKey(),
                    'rejected_at' => now(),
                    'review_note' => trim($note),
                ])->save();

                $this->events->user(
                    $import,
                    'rejected',
                    $fromStatus,
                    AnalyzerResultImport::STATUS_REJECTED,
                    $actor,
                    [
                        'note' => trim($note),
                    ]
                );

                return $import->fresh([
                    'instrument',
                    'message',
                    'specimen',
                    'orderItem.order',
                    'testResult',
                    'events' => fn ($query) => $query
                        ->orderBy('occurred_at'),
                ]);
            },
            3
        );
    }
}
