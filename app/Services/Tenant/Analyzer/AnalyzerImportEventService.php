<?php

namespace App\Services\Tenant\Analyzer;

use App\Models\AnalyzerImportEvent;
use App\Models\AnalyzerResultImport;
use App\Models\Tenant\User as TenantUser;

class AnalyzerImportEventService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function machine(
        AnalyzerResultImport $import,
        string $eventType,
        ?string $fromStatus,
        ?string $toStatus,
        array $payload = []
    ): AnalyzerImportEvent {
        return $this->record(
            $import,
            $eventType,
            $fromStatus,
            $toStatus,
            'machine',
            $import->analyzer_instrument_id,
            null,
            $payload
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function user(
        AnalyzerResultImport $import,
        string $eventType,
        ?string $fromStatus,
        ?string $toStatus,
        TenantUser $actor,
        array $payload = []
    ): AnalyzerImportEvent {
        return $this->record(
            $import,
            $eventType,
            $fromStatus,
            $toStatus,
            'tenant_user',
            $actor->getKey(),
            [
                'id' => $actor->getKey(),
                'name' => $actor->name,
                'email' => $actor->email,
                'role' => $actor->role,
            ],
            $payload
        );
    }

    /**
     * @param  array<string, mixed>|null  $actorSnapshot
     * @param  array<string, mixed>  $payload
     */
    private function record(
        AnalyzerResultImport $import,
        string $eventType,
        ?string $fromStatus,
        ?string $toStatus,
        string $actorType,
        ?int $actorId,
        ?array $actorSnapshot,
        array $payload
    ): AnalyzerImportEvent {
        return AnalyzerImportEvent::query()->create([
            'analyzer_result_import_id' => $import->getKey(),
            'event_type' => $eventType,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'actor_snapshot' => $actorSnapshot,
            'payload' => $payload,
        ]);
    }
}
