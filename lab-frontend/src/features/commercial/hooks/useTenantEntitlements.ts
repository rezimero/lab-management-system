import {
  useEffect,
  useState,
} from 'react';

import {
  useAuth,
} from '../../../contexts/AuthContext';

import {
  fetchTenantEntitlements,
} from '../api/commercialApi';

import type {
  TenantEntitlementSummary,
} from '../types/commercial.types';

const entitlementCache = new Map<number, TenantEntitlementSummary>();
const pendingRequests = new Map<number, Promise<TenantEntitlementSummary>>();

interface TenantEntitlementState {
  data?: TenantEntitlementSummary;
  error: unknown;
  isPending: boolean;
  isError: boolean;
}

export const useTenantEntitlements = (): TenantEntitlementState => {
  const auth = useAuth();
  const tenantId = auth.tenant?.id;
  const enabled = Boolean(
    auth.isAuthenticated
    && tenantId
  );

  const [state, setState] = useState<TenantEntitlementState>(() => ({
    data: tenantId ? entitlementCache.get(tenantId) : undefined,
    error: null,
    isPending: Boolean(
      enabled
      && tenantId
      && !entitlementCache.has(tenantId)
    ),
    isError: false,
  }));

  useEffect(() => {
    let active = true;

    if (!enabled || !tenantId) {
      setState({
        data: undefined,
        error: null,
        isPending: false,
        isError: false,
      });

      return () => {
        active = false;
      };
    }

    const cached = entitlementCache.get(tenantId);
    if (cached) {
      setState({
        data: cached,
        error: null,
        isPending: false,
        isError: false,
      });

      return () => {
        active = false;
      };
    }

    setState({
      data: undefined,
      error: null,
      isPending: true,
      isError: false,
    });

    let request = pendingRequests.get(tenantId);
    if (!request) {
      request = fetchTenantEntitlements();
      pendingRequests.set(tenantId, request);
    }

    void request
      .then((summary) => {
        entitlementCache.set(tenantId, summary);
        pendingRequests.delete(tenantId);

        if (active) {
          setState({
            data: summary,
            error: null,
            isPending: false,
            isError: false,
          });
        }
      })
      .catch((error: unknown) => {
        pendingRequests.delete(tenantId);

        if (active) {
          setState({
            data: undefined,
            error,
            isPending: false,
            isError: true,
          });
        }
      });

    return () => {
      active = false;
    };
  }, [enabled, tenantId]);

  return state;
};
