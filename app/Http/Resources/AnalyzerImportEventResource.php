<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnalyzerImportEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'event_type' => $this->event_type,
            'from_status' => $this->from_status,
            'to_status' => $this->to_status,
            'actor_type' => $this->actor_type,
            'actor_snapshot' => $this->actor_snapshot,
            'payload' => $this->payload,
            'occurred_at' => $this->occurred_at?->toISOString(),
        ];
    }
}
