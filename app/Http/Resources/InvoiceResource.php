<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'invoice_number' => $this->invoice_number,
            'status' => $this->status,
            'currency' => $this->currency,
            'subtotal' => $this->subtotal,
            'discount_amount' => $this->discount_amount,
            'tax_amount' => $this->tax_amount,
            'total_amount' => $this->total_amount,
            'amount_paid' => $this->amount_paid,
            'balance_due' => $this->balance_due,
            'notes' => $this->notes,
            'issued_at' => $this->issued_at?->toISOString(),
            'due_at' => $this->due_at?->toISOString(),
            'paid_at' => $this->paid_at?->toISOString(),
            'voided_at' => $this->voided_at?->toISOString(),
            'order' => $this->whenLoaded('order', fn (): ?array => $this->order === null ? null : [
                'uuid' => $this->order->uuid,
                'order_number' => $this->order->order_number,
                'status' => $this->order->status,
            ]),
            'patient' => $this->whenLoaded('patient', fn (): ?array => $this->patient === null ? null : [
                'id' => $this->patient->getKey(),
                'medical_record_number' => $this->patient->medical_record_number,
                'full_name' => $this->patient->full_name,
            ]),
            'branch' => $this->whenLoaded('branch', fn (): array => [
                'uuid' => $this->branch->uuid,
                'code' => $this->branch->code,
                'name' => $this->branch->name,
            ]),
            'corporate_account' => $this->whenLoaded('corporateAccount', fn (): ?array => $this->corporateAccount === null ? null : [
                'uuid' => $this->corporateAccount->uuid,
                'code' => $this->corporateAccount->code,
                'name' => $this->corporateAccount->name,
            ]),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item): array => [
                'id' => $item->getKey(),
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'line_total' => $item->line_total,
            ])->values()),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
            'payments_count' => $this->whenCounted('payments'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
