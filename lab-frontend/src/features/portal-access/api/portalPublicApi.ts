import axios from 'axios';

import type {
  PortalActivationResult,
  PortalInvitationInspection,
} from '../types/portalAccessAdmin.types';

export interface PortalTenantContext {
  domain: string;
  name: string;
}

const apiUrl =
  import.meta.env.VITE_API_URL?.trim()
  || 'http://127.0.0.1:8000/api';

const portalPublicClient = axios.create({
  baseURL: apiUrl,
  headers: {
    Accept: 'application/json',
  },
  withCredentials: false,
});

export const fetchPortalTenantContext =
  async (
    domain: string
  ): Promise<PortalTenantContext> => {
    const normalizedDomain =
      domain.trim().toLowerCase();

    const response =
      await portalPublicClient.get<
        PortalTenantContext
      >(
        '/portal/context',
        {
          params: {
            domain: normalizedDomain,
          },
          headers: {
            'X-Tenant-Domain':
              normalizedDomain,
          },
        }
      );

    return response.data;
  };

const portalActivationHeaders = (
  domain: string
) => ({
  'X-Tenant-Domain': domain.trim().toLowerCase(),
});

export const inspectPortalInvitation = async (
  domain: string,
  token: string
): Promise<PortalInvitationInspection> => {
  const response = await portalPublicClient.post<
    PortalInvitationInspection
  >(
    '/portal/activation/inspect',
    { token },
    {
      headers: portalActivationHeaders(domain),
    }
  );

  return response.data;
};

export const activatePortalInvitation = async (
  domain: string,
  token: string,
  password: string,
  passwordConfirmation: string
): Promise<PortalActivationResult> => {
  const response = await portalPublicClient.post<
    PortalActivationResult
  >(
    '/portal/activation/complete',
    {
      token,
      password,
      password_confirmation: passwordConfirmation,
    },
    {
      headers: portalActivationHeaders(domain),
    }
  );

  return response.data;
};
