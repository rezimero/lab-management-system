<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TestResultResource extends JsonResource
{
    /**
     * Transform a test result.
     */
    public function toArray(
        Request $request
    ): array {
        return [
            'uuid' => $this->uuid,
            'status' => $this->status,
            'results_data' => (
                $this->results_data ?? []
            ),
            'notes' => $this->notes,
            'rejection_reason' => (
                $this->rejection_reason
            ),

            'order_item' => $this->when(
                $this->relationLoaded(
                    'orderItem'
                ),
                fn (): ?array => (
                    $this->orderItem === null
                        ? null
                        : [
                            'uuid' => (
                                $this->orderItem->uuid
                            ),
                            'test_name' => (
                                $this->orderItem
                                    ->test_name_snapshot
                            ),
                            'category' => (
                                $this->orderItem
                                    ->test_category_snapshot
                            ),
                            'status' => (
                                $this->orderItem->status
                            ),
                            'parameters' => (
                                $this->orderItem
                                    ->parameters_snapshot
                            ),
                            'order' => (
                                $this->orderSummary()
                            ),
                        ]
                )
            ),

            'entered_by' => $this->when(
                $this->relationLoaded(
                    'enteredBy'
                ),
                fn (): ?array => (
                    $this->userSummary(
                        $this->enteredBy
                    )
                )
            ),

            'submitted_by' => $this->when(
                $this->relationLoaded(
                    'submittedBy'
                ),
                fn (): ?array => (
                    $this->userSummary(
                        $this->submittedBy
                    )
                )
            ),

            'reviewed_by' => $this->when(
                $this->relationLoaded(
                    'reviewedBy'
                ),
                fn (): ?array => (
                    $this->userSummary(
                        $this->reviewedBy
                    )
                )
            ),

            'submitted_at' => (
                $this->submitted_at
                    ?->toISOString()
            ),
            'reviewed_at' => (
                $this->reviewed_at
                    ?->toISOString()
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

    /**
     * Return the loaded order and patient summary.
     */
    private function orderSummary(): ?array
    {
        $item = $this->orderItem;

        if (
            $item === null
            || ! $item->relationLoaded('order')
            || $item->order === null
        ) {
            return null;
        }

        $order = $item->order;

        return [
            'uuid' => $order->uuid,
            'order_number' => (
                $order->order_number
            ),
            'priority' => $order->priority,
            'status' => $order->status,
            'patient' => (
                $order->relationLoaded('patient')
                && $order->patient !== null
                    ? [
                        'id' => $order->patient->id,
                        'medical_record_number' => (
                            $order->patient
                                ->medical_record_number
                        ),
                        'first_name' => (
                            $order->patient->first_name
                        ),
                        'last_name' => (
                            $order->patient->last_name
                        ),
                        'full_name' => (
                            $order->patient->full_name
                        ),
                    ]
                    : null
            ),
        ];
    }

    /**
     * Return a compact user summary.
     */
    private function userSummary(
        mixed $user
    ): ?array {
        if ($user === null) {
            return null;
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ];
    }
}
