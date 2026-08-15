<?php

namespace App\Services\Tenant\Notification;

use App\Models\ClinicianPortalAccount;
use App\Models\PatientPortalAccount;
use App\Models\Tenant;

class ReportReadyNotificationService
{
    public function __construct(
        private readonly NotificationPublisher $publisher
    ) {}

    public function patient(
        PatientPortalAccount $account,
        string $reportUuid
    ): void {
        $path = '/patient-portal/reports/'.$reportUuid;

        $this->publisher->publish(
            recipient: NotificationRecipient::patientPortal($account),
            idempotencyKey: 'patient-report-ready:'
                .$reportUuid.':'.$account->uuid,
            category: 'report_ready',
            eventKey: 'laboratory_report_available',
            title: 'Laboratory report available',
            body: 'A new laboratory report is available in your secure portal.',
            actionUrl: $path,
            data: [
                'report_uuid' => $reportUuid,
            ],
            email: [
                'subject' => 'A laboratory report is available',
                'title' => 'Laboratory report available',
                'body' => (
                    'A new report is available. Sign in to the secure patient '
                    .'portal to view it; clinical details are not included in '
                    .'this email.'
                ),
                'action_label' => 'Open patient portal',
                'action_url' => $this->portalUrl($path),
            ],
        );
    }

    public function clinician(
        ClinicianPortalAccount $account,
        string $reportUuid
    ): void {
        $path = '/clinician-portal/reports/'.$reportUuid;

        $this->publisher->publish(
            recipient: NotificationRecipient::clinicianPortal($account),
            idempotencyKey: 'clinician-report-ready:'
                .$reportUuid.':'.$account->uuid,
            category: 'report_ready',
            eventKey: 'laboratory_report_available',
            title: 'Laboratory report available',
            body: 'A new linked laboratory report is available in your secure portal.',
            actionUrl: $path,
            data: [
                'report_uuid' => $reportUuid,
            ],
            email: [
                'subject' => 'A laboratory report is available',
                'title' => 'Laboratory report available',
                'body' => (
                    'A new linked report is available. Sign in to the secure '
                    .'clinician portal to view it; clinical details are not '
                    .'included in this email.'
                ),
                'action_label' => 'Open clinician portal',
                'action_url' => $this->portalUrl($path),
            ],
        );
    }

    private function portalUrl(string $path): string
    {
        $url = rtrim(
            (string) config(
                'rezhel_notifications.portal_frontend_url'
            ),
            '/'
        ).$path;
        $tenant = Tenant::current();

        if ($tenant instanceof Tenant) {
            $url .= '?tenant='.rawurlencode($tenant->domain);
        }

        return $url;
    }
}
