import {
  Tenant,
  User,
} from '../types';

import { ImpersonationResponse } from './authTypes';

export const normaliseImpersonatedUser = (
  user: ImpersonationResponse['user']
): User => {
  return {
    id: user.id,
    name: user.name,
    email: user.email,
    role: user.role,
    is_active:
      user.is_active ?? true,
    created_at:
      user.created_at ?? '',
    updated_at:
      user.updated_at ?? '',
  };
};

export const normaliseImpersonatedTenant = (
  tenant: ImpersonationResponse['tenant']
): Tenant => {
  return {
    id: tenant.id,
    name: tenant.name,
    domain: tenant.domain,
    database:
      tenant.database ?? '',
    is_active:
      tenant.is_active ?? true,
    trial_ends_at:
      tenant.trial_ends_at ?? null,
    created_at:
      tenant.created_at ?? '',
    updated_at:
      tenant.updated_at ?? '',
  };
};
