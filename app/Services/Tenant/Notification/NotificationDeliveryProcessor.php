<?php

namespace App\Services\Tenant\Notification;

use App\Mail\NotificationEmail;
use App\Models\NotificationDelivery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class NotificationDeliveryProcessor
{
    public function deliver(int $deliveryId): void
    {
        $delivery = DB::connection('tenant')->transaction(
            function () use (
                $deliveryId
            ): ?NotificationDelivery {
                $candidate = NotificationDelivery::query()
                    ->whereKey($deliveryId)
                    ->lockForUpdate()
                    ->first();

                if (! $candidate instanceof NotificationDelivery) {
                    return null;
                }

                if (! in_array(
                    $candidate->status,
                    [
                        NotificationDelivery::STATUS_PENDING,
                        NotificationDelivery::STATUS_RETRYING,
                    ],
                    true
                )) {
                    return null;
                }

                if (
                    $candidate->status
                        === NotificationDelivery::STATUS_RETRYING
                    && $candidate->next_attempt_at !== null
                    && $candidate->next_attempt_at->isFuture()
                ) {
                    return null;
                }

                if ($candidate->attempts >= $candidate->max_attempts) {
                    $candidate->forceFill([
                        'status' => NotificationDelivery::STATUS_FAILED,
                        'failed_at' => now(),
                    ])->save();

                    return null;
                }

                $candidate->forceFill([
                    'status' => NotificationDelivery::STATUS_PROCESSING,
                    'attempts' => $candidate->attempts + 1,
                    'processing_started_at' => now(),
                    'next_attempt_at' => null,
                    'last_error_code' => null,
                    'last_error' => null,
                ])->save();

                return $candidate->refresh();
            },
            3
        );

        if (! $delivery instanceof NotificationDelivery) {
            return;
        }

        try {
            if ($delivery->channel !== 'email') {
                throw new \LogicException(
                    'Unsupported notification channel.'
                );
            }

            $payload = is_array($delivery->payload)
                ? $delivery->payload
                : [];

            $mailer = (string) config('mail.default', 'log');

            if (in_array($mailer, ['failover', 'roundrobin'], true)) {
                throw new \LogicException(
                    'Composite mail transports are not approved for sensitive notifications.'
                );
            }

            if ($mailer !== 'log') {
                Mail::to((string) $delivery->destination)->send(
                    new NotificationEmail($payload)
                );
            }

            $status = in_array(
                $mailer,
                ['array', 'log'],
                true
            )
                ? NotificationDelivery::STATUS_SIMULATED
                : NotificationDelivery::STATUS_SENT;

            $delivery->forceFill([
                'status' => $status,
                'sent_at' => now(),
                'processing_started_at' => null,
                'destination' => '[redacted after delivery]',
                'payload' => [
                    'redacted_after_delivery' => true,
                ],
                'provider' => $mailer,
            ])->save();
        } catch (Throwable $exception) {
            $attempts = (int) $delivery->attempts;
            $maxAttempts = (int) $delivery->max_attempts;
            $terminal = $attempts >= $maxAttempts;

            $delivery->forceFill([
                'status' => $terminal
                    ? NotificationDelivery::STATUS_FAILED
                    : NotificationDelivery::STATUS_RETRYING,
                'next_attempt_at' => $terminal
                    ? null
                    : now()->addSeconds(
                        $this->backoffSeconds($attempts)
                    ),
                'processing_started_at' => null,
                'failed_at' => $terminal ? now() : null,
                'last_error_code' => class_basename($exception),
                'last_error' => (
                    'Delivery attempt failed. Inspect application logs using '
                    .'delivery UUID '.$delivery->uuid.'.'
                ),
                'provider' => (string) config('mail.default', 'log'),
            ])->save();

            Log::error('Notification delivery failed.', [
                'delivery_uuid' => $delivery->uuid,
                'channel' => $delivery->channel,
                'attempt' => $attempts,
                'terminal' => $terminal,
                'exception_type' => class_basename($exception),
            ]);

            throw $exception;
        }
    }

    private function backoffSeconds(int $attempt): int
    {
        $backoff = config(
            'rezhel_notifications.email_backoff_seconds',
            [60, 300, 900, 3600]
        );
        $backoff = is_array($backoff) && $backoff !== []
            ? array_values($backoff)
            : [60];
        $index = min(max(0, $attempt - 1), count($backoff) - 1);

        return max(1, (int) $backoff[$index]);
    }
}
