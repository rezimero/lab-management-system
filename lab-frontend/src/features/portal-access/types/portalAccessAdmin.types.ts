export type PortalAccountType =
  | 'patient'
  | 'clinician';

export type PortalInvitationStatus =
  | 'pending'
  | 'used'
  | 'revoked'
  | 'expired';

export interface PortalInvitationSummary {
  uuid: string;
  status: PortalInvitationStatus;
  expires_at: string | null;
  used_at: string | null;
  revoked_at: string | null;
  created_at: string | null;
}

export interface PortalAccountSummary {
  uuid: string;
  email: string;
  is_active: boolean;
  activated_at: string | null;
  last_login_at: string | null;
  created_at: string | null;
  updated_at: string | null;
  invitation: PortalInvitationSummary | null;
}

export interface PortalAccessSubject {
  id?: number;
  uuid?: string;
  name: string;
  email: string | null;
  identifier: string | null;
  organisation?: string | null;
  is_active: boolean;
}

export interface PortalAccessRow {
  subject_type: PortalAccountType;
  subject: PortalAccessSubject;
  account: PortalAccountSummary | null;
}

export interface PortalAccessPage {
  data: PortalAccessRow[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from?: number | null;
    to?: number | null;
  };
}

export interface PortalActivationSecret {
  token: string;
  tenant_domain: string;
  path: string;
  expires_at: string | null;
}

export interface PortalInvitationIssueResult {
  message: string;
  account: PortalAccountSummary;
  activation: PortalActivationSecret;
}

export interface PortalInvitationInspection {
  account_type: PortalAccountType;
  email: string;
  display_name: string;
  expires_at: string | null;
}

export interface PortalActivationResult {
  message: string;
  account_type: PortalAccountType;
  email: string;
}

export interface PortalPasswordResetInspection {
  account_type: PortalAccountType;
  email_hint: string;
  expires_at: string | null;
}

export interface PortalPasswordResetResult {
  message: string;
  account_type: PortalAccountType;
}
