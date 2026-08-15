<?php

namespace App\Http\Resources\Notification;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CriticalResultEscalationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $result = $this->relationLoaded('testResult')
            ? $this->testResult
            : null;
        $order = $result?->orderItem?->order;
        $patient = $order?->patient;

        return [
            'uuid' => $this->uuid,
            'test_result_uuid' => $this->test_result_uuid,
            'order_uuid' => $this->order_uuid,
            'order_number' => $order?->order_number,
            'patient' => $patient === null
                ? null
                : [
                    'name' => $patient->full_name,
                    'medical_record_number' => (
                        $patient->medical_record_number
                    ),
                ],
            'test_name' => $result?->orderItem?->test_name_snapshot,
            'critical_count' => (int) $this->critical_count,
            'status' => $this->status,
            'escalation_level' => (int) $this->escalation_level,
            'detected_at' => $this->detected_at?->toIso8601String(),
            'acknowledgement_due_at' => (
                $this->acknowledgement_due_at?->toIso8601String()
            ),
            'acknowledged_at' => (
                $this->acknowledged_at?->toIso8601String()
            ),
            'acknowledgement_note' => $this->acknowledgement_note,
            'resolved_at' => $this->resolved_at?->toIso8601String(),
            'resolution_note' => $this->resolution_note,
            'last_escalated_at' => (
                $this->last_escalated_at?->toIso8601String()
            ),
        ];
    }
}
