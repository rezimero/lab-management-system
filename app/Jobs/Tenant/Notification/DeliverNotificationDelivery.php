<?php

namespace App\Jobs\Tenant\Notification;

use App\Models\NotificationDelivery;
use App\Models\Tenant;
use App\Services\Tenant\Notification\NotificationDeliveryProcessor;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Spatie\Multitenancy\Jobs\TenantAware;
use Throwable;

class DeliverNotificationDelivery implements ShouldBeUnique, ShouldQueue, TenantAware
{
    use Queueable;

    public int $tries = 5;

    public int $uniqueFor = 3600;

    public function __construct(
        public readonly int $tenantId,
        public readonly int $deliveryId
    ) {}

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [60, 300, 900, 3600];
    }

    public function uniqueId(): string
    {
        return 'notification-delivery-'
            .$this->tenantId.'-'.$this->deliveryId;
    }

    public function handle(
        NotificationDeliveryProcessor $processor
    ): void {
        $tenant = Tenant::current();

        if (
            ! $tenant instanceof Tenant
            || (int) $tenant->getKey() !== $this->tenantId
        ) {
            throw new \LogicException(
                'Notification delivery tenant context is invalid.'
            );
        }

        $processor->deliver($this->deliveryId);
    }

    public function failed(Throwable $exception): void
    {
        $delivery = NotificationDelivery::query()
            ->find($this->deliveryId);

        if (! $delivery instanceof NotificationDelivery) {
            return;
        }

        if (in_array(
            $delivery->status,
            [
                NotificationDelivery::STATUS_SENT,
                NotificationDelivery::STATUS_SIMULATED,
                NotificationDelivery::STATUS_SUPPRESSED,
            ],
            true
        )) {
            return;
        }

        $delivery->forceFill([
            'status' => NotificationDelivery::STATUS_FAILED,
            'failed_at' => now(),
            'processing_started_at' => null,
            'next_attempt_at' => null,
            'last_error_code' => class_basename($exception),
            'last_error' => (
                'Delivery exhausted its retry budget. Inspect application '
                .'logs using delivery UUID '.$delivery->uuid.'.'
            ),
        ])->save();
    }
}
