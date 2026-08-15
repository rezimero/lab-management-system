<?php

namespace App\Console\Commands;

use App\Jobs\Tenant\Notification\DeliverNotificationDelivery;
use App\Models\NotificationDelivery;
use App\Models\Tenant;
use App\Services\Landlord\TenantContextRunner;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class DispatchPendingNotificationDeliveries extends Command
{
    protected $signature = (
        'notifications:dispatch-pending '
        .'{--tenant= : Tenant ID or exact domain} '
        .'{--limit=100 : Maximum deliveries per tenant}'
    );

    protected $description = (
        'Dispatch due tenant notification outbox deliveries.'
    );

    public function handle(
        TenantContextRunner $runner
    ): int {
        $limit = min(1000, max(1, (int) $this->option('limit')));
        $dispatched = 0;

        $this->tenantQuery()->orderBy('id')->each(
            function (Tenant $tenant) use (
                $runner,
                $limit,
                &$dispatched
            ): void {
                $runner->run(
                    $tenant,
                    function () use (
                        $tenant,
                        $limit,
                        &$dispatched
                    ): void {
                        $staleBefore = now()->subMinutes(
                            (int) config(
                                'rezhel_notifications.processing_timeout_minutes',
                                15
                            )
                        );

                        NotificationDelivery::query()
                            ->where(
                                'status',
                                NotificationDelivery::STATUS_PROCESSING
                            )
                            ->where(
                                'processing_started_at',
                                '<=',
                                $staleBefore
                            )
                            ->whereColumn('attempts', '>=', 'max_attempts')
                            ->update([
                                'status' => NotificationDelivery::STATUS_FAILED,
                                'failed_at' => now(),
                                'processing_started_at' => null,
                                'next_attempt_at' => null,
                                'last_error_code' => 'ProcessingLeaseExpired',
                                'last_error' => (
                                    'Delivery processing stopped before completion '
                                    .'and exhausted its retry budget.'
                                ),
                                'updated_at' => now(),
                            ]);

                        NotificationDelivery::query()
                            ->where(
                                'status',
                                NotificationDelivery::STATUS_PROCESSING
                            )
                            ->where(
                                'processing_started_at',
                                '<=',
                                $staleBefore
                            )
                            ->whereColumn('attempts', '<', 'max_attempts')
                            ->update([
                                'status' => NotificationDelivery::STATUS_RETRYING,
                                'processing_started_at' => null,
                                'next_attempt_at' => now(),
                                'last_error_code' => 'ProcessingLeaseExpired',
                                'last_error' => (
                                    'Delivery processing stopped before completion '
                                    .'and was returned to the retry queue.'
                                ),
                                'updated_at' => now(),
                            ]);

                        NotificationDelivery::query()
                            ->whereIn('status', [
                                NotificationDelivery::STATUS_PENDING,
                                NotificationDelivery::STATUS_RETRYING,
                            ])
                            ->where(function ($query): void {
                                $query->whereNull('next_attempt_at')
                                    ->orWhere(
                                        'next_attempt_at',
                                        '<=',
                                        now()
                                    );
                            })
                            ->whereColumn('attempts', '<', 'max_attempts')
                            ->orderBy('id')
                            ->limit($limit)
                            ->pluck('id')
                            ->each(function (int $deliveryId) use (
                                $tenant,
                                &$dispatched
                            ): void {
                                DeliverNotificationDelivery::dispatch(
                                    (int) $tenant->getKey(),
                                    $deliveryId
                                )->onConnection((string) config(
                                    'rezhel_notifications.queue_connection',
                                    'database'
                                ));
                                $dispatched++;
                            });
                    }
                );
            }
        );

        $this->info(
            'Notification delivery jobs dispatched: '.$dispatched
        );

        return self::SUCCESS;
    }

    /**
     * @return Builder<Tenant>
     */
    private function tenantQuery(): Builder
    {
        $selector = trim((string) $this->option('tenant'));

        return Tenant::query()
            ->where('is_active', true)
            ->when(
                $selector !== '',
                static function (Builder $query) use ($selector): void {
                    $query->where(function (Builder $inner) use (
                        $selector
                    ): void {
                        if (ctype_digit($selector)) {
                            $inner->whereKey((int) $selector)
                                ->orWhere('domain', $selector);

                            return;
                        }

                        $inner->where('domain', $selector);
                    });
                }
            );
    }
}
