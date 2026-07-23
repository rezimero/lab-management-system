import {
  Tenant,
  User,
} from '../types';

export interface ImpersonationResponse {
  message: string;
  token: string;
  is_impersonating: boolean;

  user: {
    id: number;
    name: string;
    email: string;
    role: string;
    is_active?: boolean;
    created_at?: string;
    updated_at?: string;
  };

  tenant: {
    id: number;
    name: string;
    domain: string;
    database?: string;
    is_active?: boolean;
    trial_ends_at?: string | null;
    created_at?: string;
    updated_at?: string;
  };
}

export interface StoredPlatformSession {
  token: string;
  user: User;
}

export interface StoredAuthentication {
  token: string | null;
  user: User | null;
  tenant: Tenant | null;
  isSuperadmin: boolean;
  isImpersonating: boolean;
}
