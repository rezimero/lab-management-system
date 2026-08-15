<?php

namespace App\Services\Tenant\Notification;

use App\Models\NotificationMessage;
use App\Models\NotificationPreference;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class NotificationInboxService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, NotificationMessage>
     */
    public function paginate(
        NotificationRecipient $recipient,
        array $filters
    ): LengthAwarePaginator {
        return NotificationMessage::query()
            ->where('recipient_type', $recipient->type)
            ->where('recipient_id', $recipient->id)
            ->when(
                ($filters['unread'] ?? null) === true,
                static fn ($query) => $query->whereNull('read_at')
            )
            ->when(
                filled($filters['category'] ?? null),
                static fn ($query) => $query->where(
                    'category',
                    $filters['category']
                )
            )
            ->latest('created_at')
            ->paginate((int) ($filters['per_page'] ?? 20));
    }

    public function unreadCount(
        NotificationRecipient $recipient
    ): int {
        return NotificationMessage::query()
            ->where('recipient_type', $recipient->type)
            ->where('recipient_id', $recipient->id)
            ->whereNull('read_at')
            ->count();
    }

    public function markRead(
        NotificationRecipient $recipient,
        string $uuid
    ): NotificationMessage {
        return DB::connection('tenant')->transaction(
            function () use (
                $recipient,
                $uuid
            ): NotificationMessage {
                $message = NotificationMessage::query()
                    ->where('uuid', $uuid)
                    ->where('recipient_type', $recipient->type)
                    ->where('recipient_id', $recipient->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($message->read_at === null) {
                    $message->forceFill([
                        'read_at' => now(),
                    ])->save();
                }

                return $message->refresh();
            },
            3
        );
    }

    public function markAllRead(
        NotificationRecipient $recipient
    ): int {
        return NotificationMessage::query()
            ->where('recipient_type', $recipient->type)
            ->where('recipient_id', $recipient->id)
            ->whereNull('read_at')
            ->update([
                'read_at' => now(),
                'updated_at' => now(),
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function preferences(
        NotificationRecipient $recipient,
        bool $portal
    ): array {
        $categories = $portal
            ? ['report_ready']
            : ['operational', 'report_ready'];
        $saved = NotificationPreference::query()
            ->where('recipient_type', $recipient->type)
            ->where('recipient_id', $recipient->id)
            ->whereIn('category', $categories)
            ->get()
            ->keyBy('category');

        return [
            'categories' => collect($categories)
                ->map(function (string $category) use ($saved): array {
                    $preference = $saved->get($category);

                    return [
                        'category' => $category,
                        'in_app_enabled' => $preference === null
                            || (bool) $preference->in_app_enabled,
                        'email_enabled' => $preference === null
                            || (bool) $preference->email_enabled,
                    ];
                })
                ->values()
                ->all(),
            'channels' => [
                'in_app' => [
                    'enabled' => true,
                    'reason' => null,
                ],
                'email' => [
                    'enabled' => (bool) config(
                        'rezhel_notifications.email_enabled',
                        true
                    ),
                    'reason' => null,
                ],
                'sms' => config('rezhel_notifications.sms'),
                'whatsapp' => config(
                    'rezhel_notifications.whatsapp'
                ),
            ],
            'mandatory_categories' => $portal
                ? ['account_security', 'portal_access']
                : ['critical_result', 'account_security'],
        ];
    }

    public function updatePreference(
        NotificationRecipient $recipient,
        string $category,
        bool $inAppEnabled,
        bool $emailEnabled,
        bool $portal
    ): NotificationPreference {
        $allowed = $portal
            ? ['report_ready']
            : ['operational', 'report_ready'];

        abort_unless(
            in_array($category, $allowed, true),
            422,
            'This notification preference cannot be changed.'
        );

        return NotificationPreference::query()->updateOrCreate(
            [
                'recipient_type' => $recipient->type,
                'recipient_id' => $recipient->id,
                'category' => $category,
            ],
            [
                'in_app_enabled' => $inAppEnabled,
                'email_enabled' => $emailEnabled,
                'sms_enabled' => false,
                'whatsapp_enabled' => false,
            ]
        );
    }
}
