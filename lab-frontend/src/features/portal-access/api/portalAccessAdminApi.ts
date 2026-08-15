import api from '../../../api/client';

import type {
  PortalAccountSummary,
  PortalAccountType,
  PortalAccessPage,
  PortalInvitationIssueResult,
} from '../types/portalAccessAdmin.types';

export interface PortalAccessListParams {
  page?: number;
  per_page?: number;
  search?: string;
  is_active?: 0 | 1;
}

export const fetchPortalAccessSubjects = async (
  accountType: PortalAccountType,
  params: PortalAccessListParams
): Promise<PortalAccessPage> => {
  const collection = accountType === 'patient'
    ? 'patients'
    : 'clinicians';

  const response = await api.get<PortalAccessPage>(
    `/portal-access/${collection}`,
    { params }
  );

  return response.data;
};

export const issuePortalInvitation = async (
  accountType: PortalAccountType,
  subjectIdentifier: number | string,
  email: string
): Promise<PortalInvitationIssueResult> => {
  const collection = accountType === 'patient'
    ? 'patients'
    : 'clinicians';

  const response = await api.post<
    PortalInvitationIssueResult
  >(
    `/portal-access/${collection}/${subjectIdentifier}/invitations`,
    { email }
  );

  return response.data;
};

interface AccountResponse {
  data: PortalAccountSummary;
  message: string;
}

export const setPortalAccountActive = async (
  accountType: PortalAccountType,
  accountUuid: string,
  isActive: boolean
): Promise<PortalAccountSummary> => {
  const collection = accountType === 'patient'
    ? 'patient-accounts'
    : 'clinician-accounts';

  const action = isActive
    ? 'activate'
    : 'deactivate';

  const response = await api.patch<AccountResponse>(
    `/portal-access/${collection}/${accountUuid}/${action}`
  );

  return response.data.data;
};

export const revokePortalInvitation = async (
  invitationUuid: string
): Promise<void> => {
  await api.delete(
    `/portal-access/invitations/${invitationUuid}`
  );
};

export const sendPortalPasswordReset = async (
  accountType: PortalAccountType,
  accountUuid: string
): Promise<string> => {
  const collection = accountType === 'patient'
    ? 'patient-accounts'
    : 'clinician-accounts';

  const response = await api.post<{
    message: string;
  }>(
    `/portal-access/${collection}/${accountUuid}/password-reset`
  );

  return response.data.message;
};
