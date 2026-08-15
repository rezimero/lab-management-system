<?php

namespace App\Services\Tenant\Notification;

use App\Jobs\Tenant\Notification\DeliverNotificationDelivery;
use App\Models\NotificationDelivery;
use App\Models\NotificationMessage;
use App\Models\NotificationPreference;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class NotificationPublisher
{
    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>|null  $email
     */
    public function publish(
        NotificationRecipient $recipient,
        string $idempotencyKey,
        string $category,
        string $eventKey,
        string $title,
        string $body,
        ?string $actionUrl = null,
        string $priority = 'normal',
        array $data = [],
        ?array $email = null,
        bool $inApp = true,
        bool $mandatory = false
    ): ?NotificationMessage {
        $preference = $this->preference(
            $recipient,
            $category
        );
        $inAppEnabled = $mandatory
            || $preference === null
            || $preference->in_app_enabled;
        $emailEnabled = $mandatory
            || $preference === null
            || $preference->email_enabled;

        $published = DB::connection('tenant')->transaction(
            function () use (
                $recipient,
                $idempotencyKey,
                $category,
                $eventKey,
                $title,
                $body,
                $actionUrl,
                $priority,
                $data,
                $email,
                $inApp,
                $inAppEnabled,
                $emailEnabled
            ): array {
                $message = null;

                if ($inApp && $inAppEnabled) {
                    $message = NotificationMessage::query()
                        ->firstOrCreate(
                            [
                                'idempotency_key' => $idempotencyKey,
                            ],
                            [
                                'recipient_type' => $recipient->type,
                                'recipient_id' => $recipient->id,
                                'recipient_uuid' => $recipient->uuid,
                                'category' => $category,
                                'event_key' => $eventKey,
                                'priority' => $priority,
                                'title' => $title,
                                'body' => $body,
                                'action_url' => $actionUrl,
                                'data' => $data === []
                                    ? null
                                    : $data,
                            ]
                        );
                }

                $delivery = $this->emailDelivery(
                    recipient: $recipient,
                    message: $message,
                    idempotencyKey: $idempotencyKey,
                    email: $email,
                    emailEnabled: $emailEnabled,
                );

                return [
                    'message' => $message,
                    'delivery' => $delivery,
                ];
            },
            3
        );

        $delivery = $published['delivery'];

        if (
            $delivery instanceof NotificationDelivery
            && in_array(
                $delivery->status,
                [
                    NotificationDelivery::STATUS_PENDING,
                    NotificationDelivery::STATUS_RETRYING,
                ],
                true
            )
        ) {
            $tenant = Tenant::current();

            if (! $tenant instanceof Tenant) {
                throw new \LogicException(
                    'No active tenant is available for notification delivery.'
                );
            }

            try {
                DeliverNotificationDelivery::dispatch(
                    (int) $tenant->getKey(),
                    (int) $delivery->getKey()
                )
                    ->onConnection((string) config(
                        'rezhel_notifications.queue_connection',
                        'database'
                    ))
                    ->afterCommit();
            } catch (Throwable $exception) {
                Log::warning(
                    'Notification outbox dispatch was deferred.',
                    [
                        'delivery_uuid' => $delivery->uuid,
                        'exception_type' => class_basename($exception),
                    ]
                );
            }
        }

        return $published['message'];
    }

    private function preference(
        NotificationRecipient $recipient,
        string $category
    ): ?NotificationPreference {
        return NotificationPreference::query()
            ->where('recipient_type', $recipient->type)
            ->where('recipient_id', $recipient->id)
            ->where('category', $category)
            ->first();
    }

    /**
     * @param  array<string, mixed>|null  $email
     */
    private function emailDelivery(
        NotificationRecipient $recipient,
        ?NotificationMessage $message,
        string $idempotencyKey,
        ?array $email,
        bool $emailEnabled
    ): ?NotificationDelivery {
        if ($email === null || $recipient->email === null) {
            return null;
        }

        $globallyEnabled = (bool) config(
            'rezhel_notifications.email_enabled',
            true
        );
        $status = $globallyEnabled && $emailEnabled
            ? NotificationDelivery::STATUS_PENDING
            : NotificationDelivery::STATUS_SUPPRESSED;

        return NotificationDelivery::query()->firstOrCreate(
            [
                'idempotency_key' => $idempotencyKey.':email',
            ],
            [
                'notification_message_id' => $message?->getKey(),
                'recipient_type' => $recipient->type,
                'recipient_id' => $recipient->id,
                'recipient_uuid' => $recipient->uuid,
                'channel' => 'email',
                'destination_hash' => hash_hmac(
                    'sha256',
                    $recipient->email,
                    (string) config('app.key')
                ),
                'destination' => $recipient->email,
                'payload' => $email,
                'status' => $status,
                'max_attempts' => max(
                    1,
                    (int) config(
                        'rezhel_notifications.email_max_attempts',
                        5
                    )
                ),
                'next_attempt_at' => $status
                    === NotificationDelivery::STATUS_PENDING
                    ? now()
                    : null,
                'provider' => (string) config(
                    'mail.default',
                    'log'
                ),
            ]
        );
    }
}
