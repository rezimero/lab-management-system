<?php

namespace App\Services\Tenant\Notification;

use App\Models\ClinicianPortalAccount;
use App\Models\PatientPortalAccount;
use App\Models\PortalInvitation;

class PortalAccessNotificationService
{
    public function __construct(
        private readonly NotificationPublisher $publisher
    ) {}

    public function invitation(
        string $accountType,
        PatientPortalAccount|ClinicianPortalAccount $account,
        string $invitationUuid,
        string $plainTextToken,
        string $tenantDomain
    ): void {
        $patient = $accountType === PortalInvitation::ACCOUNT_PATIENT;
        $recipient = $account instanceof PatientPortalAccount
            ? NotificationRecipient::patientPortal($account)
            : NotificationRecipient::clinicianPortal($account);
        $portalName = $patient
            ? 'patient portal'
            : 'clinician portal';

        $this->publisher->publish(
            recipient: $recipient,
            idempotencyKey: $accountType.'-portal-invitation:'
                .$invitationUuid,
            category: 'portal_access',
            eventKey: $accountType.'_portal_invitation_created',
            title: 'Activate your '.$portalName,
            body: (
                'A one-time '.$portalName.' invitation has been created. '
                .'Use the secure link before it expires.'
            ),
            inApp: false,
            mandatory: true,
            email: [
                'subject' => 'Activate your '.$portalName,
                'title' => 'Activate your '.$portalName,
                'body' => (
                    'A secure one-time invitation is ready. The link expires '
                    .'automatically and can be used only once.'
                ),
                'action_label' => 'Activate portal access',
                'action_url' => $this->activationUrl(
                    $tenantDomain,
                    $plainTextToken
                ),
                'footer' => (
                    'If you were not expecting this invitation, contact the '
                    .'laboratory using a trusted phone number.'
                ),
            ],
        );
    }

    private function activationUrl(
        string $tenantDomain,
        string $token
    ): string {
        $base = (string) config(
            'rezhel_notifications.portal_frontend_url'
        );
        $path = '/'.ltrim(
            (string) config(
                'portal.activation_path',
                '/portal/activate'
            ),
            '/'
        );

        return $base.$path
            .'?tenant='.rawurlencode($tenantDomain)
            .'#token='.rawurlencode($token);
    }
}
