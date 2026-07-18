<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LaboratoryOrderItemResource extends JsonResource
{
    /**
     * Transform an order item.
     */
    public function toArray(
        Request $request
    ): array {
        return [
            'uuid' => $this->uuid,
            'lab_test_id' => $this->lab_test_id,
            'test_name' => (
                $this->test_name_snapshot
            ),
            'category' => (
                $this->test_category_snapshot
            ),
            'price' => $this->price_snapshot,
            'turnaround_time_hours' => (
                $this
                    ->turnaround_time_hours_snapshot
            ),
            'parameters' => (
                $this->parameters_snapshot
            ),
            'status' => $this->status,

            'processing_started_by' => (
                $this->when(
                    $this->relationLoaded(
                        'processingStartedBy'
                    ),
                    fn (): ?array => (
                        $this->processingStartedBy
                            === null
                                ? null
                                : [
                                    'id' => (
                                        $this
                                            ->processingStartedBy
                                            ->id
                                    ),
                                    'name' => (
                                        $this
                                            ->processingStartedBy
                                            ->name
                                    ),
                                    'email' => (
                                        $this
                                            ->processingStartedBy
                                            ->email
                                    ),
                                ]
                    )
                )
            ),

            'processing_started_at' => (
                $this->processing_started_at
                    ?->toISOString()
            ),
            'completed_at' => (
                $this->completed_at
                    ?->toISOString()
            ),

            'test_result' => (
                new TestResultResource(
                    $this->whenLoaded(
                        'testResult'
                    )
                )
            ),

            'created_at' => (
                $this->created_at
                    ?->toISOString()
            ),
            'updated_at' => (
                $this->updated_at
                    ?->toISOString()
            ),
        ];
    }
}
