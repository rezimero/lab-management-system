<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\IndexPortalAccessSubjectRequest;
use App\Http\Requests\Portal\IssuePortalInvitationRequest;
use App\Http\Resources\Portal\ClinicianPortalAccessSubjectResource;
use App\Http\Resources\Portal\PatientPortalAccessSubjectResource;
use App\Http\Resources\Portal\PortalAccountAdministrationResource;
use App\Models\PortalInvitation;
use App\Models\Tenant\User as TenantUser;
use App\Services\Tenant\Portal\PortalAccessAdministrationQueryService;
use App\Services\Tenant\Portal\PortalInvitationService;
use App\Services\Tenant\Portal\PortalPasswordResetService;
use App\Support\Tenant\TenantUserPermissionCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class PortalAccessAdministrationController extends Controller
{
    public function __construct(
        private readonly PortalAccessAdministrationQueryService $queries,
        private readonly PortalInvitationService $invitations,
        private readonly PortalPasswordResetService $passwordResets
    ) {}

    public function patients(
        IndexPortalAccessSubjectRequest $request
    ): AnonymousResourceCollection {
        $this->authorizeManager($request);
        $validated = $request->validated();

        return PatientPortalAccessSubjectResource::collection(
            $this->queries->paginatePatients(
                search: $validated['search'] ?? null,
                isActive: array_key_exists(
                    'is_active',
                    $validated
                )
                    ? (bool) $validated['is_active']
                    : null,
                perPage: (int) (
                    $validated['per_page'] ?? 25
                ),
            )
        );
    }

    public function clinicians(
        IndexPortalAccessSubjectRequest $request
    ): AnonymousResourceCollection {
        $this->authorizeManager($request);
        $validated = $request->validated();

        return ClinicianPortalAccessSubjectResource::collection(
            $this->queries->paginateClinicians(
                search: $validated['search'] ?? null,
                isActive: array_key_exists(
                    'is_active',
                    $validated
                )
                    ? (bool) $validated['is_active']
                    : null,
                perPage: (int) (
                    $validated['per_page'] ?? 25
                ),
            )
        );
    }

    public function invitePatient(
        IssuePortalInvitationRequest $request,
        int $patient
    ): JsonResponse {
        $actor = $this->authorizeManager($request);
        $issued = $this->invitations->issuePatient(
            $patient,
            (string) $request->validated('email'),
            $actor
        );

        return $this->invitationResponse($issued);
    }

    public function inviteClinician(
        IssuePortalInvitationRequest $request,
        string $referringClinician
    ): JsonResponse {
        $actor = $this->authorizeManager($request);
        $issued = $this->invitations->issueClinician(
            $referringClinician,
            (string) $request->validated('email'),
            $actor
        );

        return $this->invitationResponse($issued);
    }

    public function activatePatientAccount(
        Request $request,
        string $account
    ): JsonResponse {
        return $this->setAccountActive(
            $request,
            PortalInvitation::ACCOUNT_PATIENT,
            $account,
            true
        );
    }

    public function deactivatePatientAccount(
        Request $request,
        string $account
    ): JsonResponse {
        return $this->setAccountActive(
            $request,
            PortalInvitation::ACCOUNT_PATIENT,
            $account,
            false
        );
    }

    public function sendPatientPasswordReset(
        Request $request,
        string $account
    ): JsonResponse {
        $this->passwordResets->requestByAdministrator(
            PortalInvitation::ACCOUNT_PATIENT,
            $account,
            $this->authorizeManager($request)
        );

        return response()->json([
            'message' => 'A secure patient password reset request was recorded for delivery.',
        ], Response::HTTP_ACCEPTED);
    }

    public function activateClinicianAccount(
        Request $request,
        string $account
    ): JsonResponse {
        return $this->setAccountActive(
            $request,
            PortalInvitation::ACCOUNT_CLINICIAN,
            $account,
            true
        );
    }

    public function deactivateClinicianAccount(
        Request $request,
        string $account
    ): JsonResponse {
        return $this->setAccountActive(
            $request,
            PortalInvitation::ACCOUNT_CLINICIAN,
            $account,
            false
        );
    }

    public function sendClinicianPasswordReset(
        Request $request,
        string $account
    ): JsonResponse {
        $this->passwordResets->requestByAdministrator(
            PortalInvitation::ACCOUNT_CLINICIAN,
            $account,
            $this->authorizeManager($request)
        );

        return response()->json([
            'message' => 'A secure clinician password reset request was recorded for delivery.',
        ], Response::HTTP_ACCEPTED);
    }

    public function revokeInvitation(
        Request $request,
        string $invitation
    ): JsonResponse {
        $actor = $this->authorizeManager($request);
        $revoked = $this->invitations->revoke(
            $invitation,
            $actor
        );

        return response()->json([
            'message' => 'Portal invitation revoked successfully.',
            'invitation' => [
                'uuid' => $revoked->uuid,
                'revoked_at' => $revoked->revoked_at
                    ?->toIso8601String(),
            ],
        ]);
    }

    private function setAccountActive(
        Request $request,
        string $accountType,
        string $accountUuid,
        bool $isActive
    ): JsonResponse {
        $actor = $this->authorizeManager($request);
        $account = $this->invitations
            ->setAccountActive(
                $accountType,
                $accountUuid,
                $isActive,
                $actor
            );

        return (new PortalAccountAdministrationResource(
            $account
        ))
            ->additional([
                'message' => $isActive
                    ? 'Portal account enabled successfully.'
                    : 'Portal account disabled successfully.',
            ])
            ->response();
    }

    /**
     * @param  array<string, mixed>  $issued
     */
    private function invitationResponse(
        array $issued
    ): JsonResponse {
        return response()->json([
            'message' => 'Portal invitation created successfully.',
            'account' => (
                new PortalAccountAdministrationResource(
                    $issued['account']
                )
            )->resolve(),
            'activation' => $issued['activation'],
        ], Response::HTTP_CREATED);
    }

    private function authorizeManager(
        Request $request
    ): TenantUser {
        $user = $request->user();

        abort_unless(
            $user instanceof TenantUser,
            401,
            'Unauthenticated.'
        );

        abort_unless(
            $user->hasPermission(
                TenantUserPermissionCatalog::MANAGE_PORTAL_ACCESS
            ),
            403,
            'You do not have permission to manage portal access.'
        );

        return $user;
    }
}
