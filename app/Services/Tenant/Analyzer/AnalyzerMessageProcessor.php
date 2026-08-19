<?php

namespace App\Services\Tenant\Analyzer;

use App\Models\AnalyzerMessage;
use App\Models\AnalyzerResultImport;
use App\Support\Tenant\AnalyzerIssueCatalog;
use Illuminate\Support\Facades\DB;
use Throwable;

class AnalyzerMessageProcessor
{
    public function __construct(
        private readonly AnalyzerCandidateMatcher $matcher,
        private readonly AnalyzerDraftImportService $drafts,
        private readonly AnalyzerImportEventService $events
    ) {}

    public function process(int $messageId): void
    {
        $message = AnalyzerMessage::query()
            ->with([
                'instrument',
                'imports' => fn ($query) => $query->orderBy('id'),
            ])
            ->findOrFail($messageId);

        $message->forceFill([
            'status' => AnalyzerMessage::STATUS_PROCESSING,
            'last_error_code' => null,
            'last_error' => null,
        ])->save();

        $payload = json_decode(
            (string) $message->raw_payload,
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $candidates = collect($payload['results'] ?? [])
            ->keyBy(
                fn (array $candidate): string => trim(
                    (string) ($candidate['result_key'] ?? '')
                )
            );

        $hadSystemFailure = false;

        foreach ($message->imports as $import) {
            if (
                in_array(
                    $import->status,
                    [
                        AnalyzerResultImport::STATUS_IMPORTED,
                        AnalyzerResultImport::STATUS_REJECTED,
                    ],
                    true
                )
            ) {
                continue;
            }

            $candidate = $candidates->get(
                $import->source_result_key
            );

            if (! is_array($candidate)) {
                $this->markFailed(
                    $import,
                    AnalyzerIssueCatalog::SCHEMA_INVALID,
                    'The stored analyzer message no longer contains the expected result candidate.'
                );
                $hadSystemFailure = true;
                continue;
            }

            try {
                $this->processImport(
                    $import,
                    $message,
                    $candidate
                );
            } catch (Throwable $exception) {
                report($exception);

                $this->markFailed(
                    $import,
                    AnalyzerIssueCatalog::PROCESSING_FAILED,
                    'Analyzer processing failed. Review application logs using the import UUID.'
                );

                $hadSystemFailure = true;
            }
        }

        $message->forceFill([
            'status' => $hadSystemFailure
                ? AnalyzerMessage::STATUS_FAILED
                : AnalyzerMessage::STATUS_PROCESSED,
            'processed_at' => now(),
            'last_error_code' => $hadSystemFailure
                ? AnalyzerIssueCatalog::PROCESSING_FAILED
                : null,
            'last_error' => $hadSystemFailure
                ? 'One or more analyzer result candidates encountered an internal processing failure.'
                : null,
        ])->save();

        if ($message->instrument !== null) {
            $message->instrument->forceFill(
                $hadSystemFailure
                    ? [
                        'last_error_at' => now(),
                        'last_error_code' => (
                            AnalyzerIssueCatalog::PROCESSING_FAILED
                        ),
                    ]
                    : [
                        'last_success_at' => now(),
                        'last_error_at' => null,
                        'last_error_code' => null,
                    ]
            )->save();
        }
    }

    /**
     * @param  array<string, mixed>  $candidate
     */
    private function processImport(
        AnalyzerResultImport $import,
        AnalyzerMessage $message,
        array $candidate
    ): void {
        DB::connection('tenant')->transaction(
            function () use (
                $import,
                $message,
                $candidate
            ): void {
                $locked = AnalyzerResultImport::query()
                    ->whereKey($import->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                $fromStatus = $locked->status;
                $resolution = $this->matcher->resolve(
                    $message->instrument,
                    $candidate
                );

                $issues = $resolution['validation_issues'];
                $status = $issues === []
                    ? AnalyzerResultImport::STATUS_READY
                    : AnalyzerResultImport::STATUS_NEEDS_REVIEW;

                $locked->forceFill([
                    'analyzer_test_mapping_id' => (
                        $resolution['analyzer_test_mapping_id']
                    ),
                    'specimen_id' => $resolution['specimen_id'],
                    'laboratory_order_item_id' => (
                        $resolution['laboratory_order_item_id']
                    ),
                    'normalized_results' => (
                        $resolution['normalized_results']
                    ),
                    'mapping_snapshot' => (
                        $resolution['mapping_snapshot']
                    ),
                    'validation_issues' => (
                        $issues === [] ? null : $issues
                    ),
                    'status' => $status,
                    'attempts' => $locked->attempts + 1,
                    'last_processed_at' => now(),
                ])->save();

                $this->events->machine(
                    $locked,
                    $status === AnalyzerResultImport::STATUS_READY
                        ? 'validated'
                        : 'validation_required',
                    $fromStatus,
                    $status,
                    [
                        'issue_codes' => collect($issues)
                            ->pluck('code')
                            ->values()
                            ->all(),
                    ]
                );
            },
            3
        );

        $import->refresh();

        if ($import->status !== AnalyzerResultImport::STATUS_READY) {
            return;
        }

        try {
            $this->drafts->create($import);
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $import->refresh();
            $fromStatus = $import->status;

            $import->forceFill([
                'status' => AnalyzerResultImport::STATUS_NEEDS_REVIEW,
                'validation_issues' => [
                    [
                        'code' => AnalyzerIssueCatalog::RESULT_CONFLICT,
                        'message' => collect($exception->errors())
                            ->flatten()
                            ->first()
                            ?? 'The analyzer result cannot be imported automatically.',
                    ],
                ],
                'last_processed_at' => now(),
            ])->save();

            $this->events->machine(
                $import,
                'validation_required',
                $fromStatus,
                AnalyzerResultImport::STATUS_NEEDS_REVIEW,
                [
                    'issue_codes' => [
                        AnalyzerIssueCatalog::RESULT_CONFLICT,
                    ],
                ]
            );
        }
    }

    private function markFailed(
        AnalyzerResultImport $import,
        string $code,
        string $message
    ): void {
        DB::connection('tenant')->transaction(
            function () use (
                $import,
                $code,
                $message
            ): void {
                $locked = AnalyzerResultImport::query()
                    ->whereKey($import->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                $fromStatus = $locked->status;

                $locked->forceFill([
                    'status' => AnalyzerResultImport::STATUS_FAILED,
                    'validation_issues' => [
                        [
                            'code' => $code,
                            'message' => $message,
                        ],
                    ],
                    'attempts' => $locked->attempts + 1,
                    'last_processed_at' => now(),
                ])->save();

                $this->events->machine(
                    $locked,
                    'processing_failed',
                    $fromStatus,
                    AnalyzerResultImport::STATUS_FAILED,
                    [
                        'issue_codes' => [$code],
                    ]
                );
            },
            3
        );
    }
}
