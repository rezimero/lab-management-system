export interface TenantRecord {
  id: number;
  name: string;
  domain: string;
  database?: string;
  is_active: boolean;
  trial_ends_at?: string | null;
  created_at?: string;

  stats?: {
    users?: number;
    patients?: number;
    lab_tests?: number;
    test_results?: number;
  };
}

export interface CreateTenantPayload {
  name: string;
  domain: string;
  admin_name: string;
  admin_email: string;
  admin_password: string;
}

export interface UpdateTenantPayload {
  name: string;
  domain: string;
  is_active: boolean;
}

export interface PasswordResetPayload {
  email: string;
  new_password: string;
}

export interface TenantIndexResponse {
  tenants:
    | TenantRecord[]
    | {
        data: TenantRecord[];
      };

  total?: number;
}

export interface TenantTotals {
  users: number;
  patients: number;
  tests: number;
  results: number;
}

export type TenantStatusAction =
  | 'suspend'
  | 'activate';

export type ConfirmationAction =
  | TenantStatusAction
  | 'delete'
  | null;
