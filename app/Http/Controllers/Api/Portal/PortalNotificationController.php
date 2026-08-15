<?php

namespace App\Http\Controllers\Api\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Notification\IndexNotificationsRequest;
use App\Http\Requests\Notification\UpdateNotificationPreferenceRequest;
use App\Http\Resources\Notification\NotificationMessageResource;
use App\Models\ClinicianPortalAccount;
use App\Models\PatientPortalAccount;
use App\Services\Tenant\Notification\NotificationInboxService;
use App\Services\Tenant\Notification\NotificationRecipient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PortalNotificationController extends Controller
{
    public function __construct(
        private readonly NotificationInboxService $inbox
    ) {}

    public function index(
        IndexNotificationsRequest $request
    ): AnonymousResourceCollection {
        $recipient = $this->recipient($request);

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
        $recipient = $this->recipient($request);

        return response()->json([
            'unread_count' => $this->inbox->unreadCount($recipient),
        ]);
    }

    public function markRead(
        Request $request,
        string $notification
    ): NotificationMessageResource {
        $recipient = $this->recipient($request);

        return new NotificationMessageResource(
            $this->inbox->markRead($recipient, $notification)
        );
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $recipient = $this->recipient($request);

        return response()->json([
            'message' => 'Notifications marked as read.',
            'updated' => $this->inbox->markAllRead($recipient),
        ]);
    }

    public function preferences(Request $request): JsonResponse
    {
        return response()->json(
            $this->inbox->preferences(
                $this->recipient($request),
                true
            )
        );
    }

    public function updatePreference(
        UpdateNotificationPreferenceRequest $request
    ): JsonResponse {
        $validated = $request->validated();
        $preference = $this->inbox->updatePreference(
            $this->recipient($request),
            (string) $validated['category'],
            (bool) $validated['in_app_enabled'],
            (bool) $validated['email_enabled'],
            true
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

    private function recipient(
        Request $request
    ): NotificationRecipient {
        $account = $request->attributes->get('portal_account');

        if ($account instanceof PatientPortalAccount) {
            return NotificationRecipient::patientPortal($account);
        }

        if ($account instanceof ClinicianPortalAccount) {
            return NotificationRecipient::clinicianPortal($account);
        }

        abort(401, 'Unauthenticated.');
    }
}
