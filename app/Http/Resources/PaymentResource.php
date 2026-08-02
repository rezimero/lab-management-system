<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'payment_number' => $this->payment_number,
            'receipt_number' => $this->receipt_number,
            'amount' => $this->amount,
            'method' => $this->method,
            'reference' => $this->reference,
            'notes' => $this->notes,
            'paid_at' => $this->paid_at?->toISOString(),
            'received_by' => $this->whenLoaded('receivedBy', fn (): ?array => $this->receivedBy === null ? null : [
                'id' => $this->receivedBy->getKey(),
                'name' => $this->receivedBy->name,
                'email' => $this->receivedBy->email,
            ]),
            'invoice' => $this->whenLoaded('invoice', fn (): array => [
                'uuid' => $this->invoice->uuid,
                'invoice_number' => $this->invoice->invoice_number,
                'status' => $this->invoice->status,
                'currency' => $this->invoice->currency,
                'total_amount' => $this->invoice->total_amount,
                'amount_paid' => $this->invoice->amount_paid,
                'balance_due' => $this->invoice->balance_due,
            ]),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
