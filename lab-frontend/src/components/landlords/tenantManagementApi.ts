import axios from 'axios';

import api from '../../api/client';

import type {
  CreateTenantPayload,
  PasswordResetPayload,
  TenantIndexResponse,
  TenantRecord,
  TenantStatusAction,
  UpdateTenantPayload,
} from './tenantManagementTypes';

export const getTenantErrorMessage = (
  error: unknown
): string => {
  if (axios.isAxiosError(error)) {
    const responseData =
      error.response?.data as
        | {
            message?: string;
            errors?: Record<
              string,
              string[]
            >;
          }
        | undefined;

    if (responseData?.errors) {
      const firstError =
        Object.values(
          responseData.errors
        )
          .flat()
          .find(Boolean);

      if (firstError) {
        return firstError;
      }
    }

    if (responseData?.message) {
      return responseData.message;
    }
  }

  if (error instanceof Error) {
    return error.message;
  }

  return 'The requested operation could not be completed.';
};

export const fetchTenants =
  async (): Promise<TenantRecord[]> => {
    const response =
      await api.get<TenantIndexResponse>(
        '/landlord/tenants'
      );

    const payload =
      response.data.tenants;

    if (Array.isArray(payload)) {
      return payload;
    }

    return payload?.data ?? [];
  };

export const createTenant = (
  payload: CreateTenantPayload
) => {
  return api.post(
    '/landlord/tenants',
    payload
  );
};

export const updateTenant = (
  tenantId: number,
  payload: UpdateTenantPayload
) => {
  return api.put(
    `/landlord/tenants/${tenantId}`,
    payload
  );
};

export const changeTenantStatus = (
  tenant: TenantRecord,
  action: TenantStatusAction
) => {
  return api.post(
    `/landlord/tenants/${tenant.id}/${action}`
  );
};

export const deleteTenant = (
  tenant: TenantRecord
) => {
  return api.delete(
    `/landlord/tenants/${tenant.id}`
  );
};

export const resetTenantAdminPassword = (
  tenantId: number,
  payload: PasswordResetPayload
) => {
  return api.post(
    `/landlord/tenants/${tenantId}/reset-admin-password`,
    payload
  );
};
