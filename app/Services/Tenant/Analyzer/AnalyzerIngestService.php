<?php

namespace App\Services\Tenant\Analyzer;

use App\Jobs\Tenant\Analyzer\ProcessAnalyzerMessage;
use App\Models\AnalyzerInstrument;
use App\Models\AnalyzerMessage;
use App\Models\AnalyzerResultImport;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class AnalyzerIngestService
{
    public function __construct(
        private readonly AnalyzerImportEventService $events
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array{message: AnalyzerMessage, duplicate: bool}
     */
    public function ingest(
        Request $request,
        AnalyzerInstrument $instrument,
        array $data
    ): array {
        $tenant = Tenant::current();

        if (! $tenant instanceof Tenant) {
            abort(422, 'Tenant context is required.');
        }

        $idempotencyKey = (string) $data['idempotency_key'];
        unset($data['idempotency_key']);

        $rawPayload = $request->getContent();
        $payloadHash = hash('sha256', $rawPayload);

        $message = DB::connection('tenant')->transaction(
            function () use (
                $instrument,
                $data,
                $idempotencyKey,
                $rawPayload,
                $payloadHash
            ): AnalyzerMessage {
                $message = AnalyzerMessage::query()->firstOrCreate(
                    [
                        'analyzer_instrument_id' => $instrument->getKey(),
                        'idempotency_key' => $idempotencyKey,
                    ],
                    [
                        'external_message_id' => $this->nullableString(
                            $data['message_id'] ?? null
                        ),
                        'adapter_key' => $instrument->adapter_key,
                        'content_type' => 'application/json',
                        'payload_hash' => $payloadHash,
                        'raw_payload' => $rawPayload,
                        'source_timestamp' => $data['sent_at'] ?? null,
                        'status' => AnalyzerMessage::STATUS_RECEIVED,
                    ]
                );

                if (
                    ! $message->wasRecentlyCreated
                    && ! hash_equals(
                        (string) $message->payload_hash,
                        $payloadHash
                    )
                ) {
                    throw new ConflictHttpException(
                        'The analyzer idempotency key was already used for a different payload.'
                    );
                }

                if (! $message->wasRecentlyCreated) {
                    return $message;
                }

                foreach ($data['results'] as $candidate) {
                    $import = AnalyzerResultImport::query()->create([
                        'analyzer_message_id' => $message->getKey(),
                        'analyzer_instrument_id' => $instrument->getKey(),
                        'source_result_key' => trim(
                            (string) $candidate['result_key']
                        ),
                        'source_sample_identifier' => strtoupper(
                            trim((string) $candidate['sample_id'])
                        ),
                        'instrument_test_code' => strtoupper(
                            trim((string) $candidate['test_code'])
                        ),
                        'status' => AnalyzerResultImport::STATUS_RECEIVED,
                    ]);

                    $this->events->machine(
                        $import,
                        'received',
                        null,
                        AnalyzerResultImport::STATUS_RECEIVED,
                        [
                            'message_uuid' => $message->uuid,
                        ]
                    );
                }

                return $message;
            },
            3
        );

        $duplicate = ! $message->wasRecentlyCreated;

        if (! $duplicate) {
            ProcessAnalyzerMessage::dispatch(
                (int) $tenant->getKey(),
                (int) $message->getKey()
            );
        }

        return [
            'message' => $message
                ->fresh()
                ->load([
                    'imports' => fn ($query) => $query
                        ->orderBy('id'),
                ]),
            'duplicate' => $duplicate,
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
