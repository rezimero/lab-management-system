<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Notification\IndexNotificationsRequest;
use App\Http\Requests\Notification\UpdateNotificationPreferenceRequest;
use App\Http\Resources\Notification\NotificationMessageResource;
use App\Models\Tenant\User as TenantUser;
use App\Services\Tenant\Notification\NotificationInboxService;
use App\Services\Tenant\Notification\NotificationRecipient;
use App\Support\Tenant\TenantUserPermissionCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NotificationController extends Controller
{
    public function __construct(
        private readonly NotificationInboxService $inbox
    ) {}

    public function index(
        IndexNotificationsRequest $request
    ): AnonymousResourceCollection {
        $recipient = NotificationRecipient::tenantUser(
            $this->user($request)
        );

        return NotificationMessageResource::collection(
            $this->inbox->paginate(
                $recipient,
                $request->validated()
            )
        )->additional([
            'unread_count' => $this->inbox->unreadCount($recipient),
        ]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $recipient = NotificationRecipient::tenantUser(
            $this->user($request)
        );

        return response()->json([
            'unread_count' => $this->inbox->unreadCount($recipient),
        ]);
    }

    public function markRead(
        Request $request,
        string $notification
    ): NotificationMessageResource {
        $recipient = NotificationRecipient::tenantUser(
            $this->user($request)
        );

        return new NotificationMessageResource(
            $this->inbox->markRead($recipient, $notification)
        );
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $recipient = NotificationRecipient::tenantUser(
            $this->user($request)
        );

        return response()->json([
            'message' => 'Notifications marked as read.',
            'updated' => $this->inbox->markAllRead($recipient),
        ]);
    }

    public function preferences(Request $request): JsonResponse
    {
        $recipient = NotificationRecipient::tenantUser(
            $this->user($request)
        );

        return response()->json(
            $this->inbox->preferences($recipient, false)
        );
    }

    public function updatePreference(
        UpdateNotificationPreferenceRequest $request
    ): JsonResponse {
        $recipient = NotificationRecipient::tenantUser(
            $this->user($request)
        );
        $validated = $request->validated();
        $preference = $this->inbox->updatePreference(
            $recipient,
            (string) $validated['category'],
            (bool) $validated['in_app_enabled'],
            (bool) $validated['email_enabled'],
            false
        );

        return response()->json([
            'message' => 'Notification preference updated.',
            'preference' => [
                'category' => $preference->category,
                'in_app_enabled' => (
                    (bool) $preference->in_app_enabled
                ),
                'email_enabled' => (
                    (bool) $preference->email_enabled
                ),
            ],
        ]);
    }

    private function user(Request $request): TenantUser
    {
        $user = $request->user();

        abort_unless($user instanceof TenantUser, 401, 'Unauthenticated.');
        abort_unless(
            $user->hasPermission(
                TenantUserPermissionCatalog::VIEW_NOTIFICATIONS
            ),
            403,
            'You do not have permission to view notifications.'
        );

        return $user;
    }
}
