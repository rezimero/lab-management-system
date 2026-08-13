import {
  createContext,
  ReactNode,
  useContext,
  useEffect,
  useMemo,
  useState,
} from 'react';

import {
  useQueryClient,
} from '@tanstack/react-query';

import {
  fetchClinicianPortalMe,
  loginClinicianPortal,
  logoutClinicianPortal,
} from '../api/clinicianPortalApi';

import {
  clearClinicianPortalSession,
  clinicianPortalSessionExpired,
  readClinicianPortalSession,
  writeClinicianPortalSession,
} from '../auth/clinicianPortalStorage';

import type {
  ClinicianPortalContext,
  ClinicianPortalLoginResponse,
} from '../types/clinicianPortal.types';

interface ClinicianPortalAuthContextValue {
  session: ClinicianPortalContext | null;
  isLoading: boolean;
  isAuthenticated: boolean;
  login: (
    domain: string,
    email: string,
    password: string
  ) => Promise<ClinicianPortalLoginResponse>;
  logout: () => Promise<void>;
  refresh: () => Promise<void>;
}

const ClinicianPortalAuthContext =
  createContext<
    ClinicianPortalAuthContextValue | undefined
  >(undefined);

interface ClinicianPortalAuthProviderProps {
  children: ReactNode;
}

const contextFromLogin = (
  response: ClinicianPortalLoginResponse
): ClinicianPortalContext => ({
  portal: response.portal,
  account: response.account,
  clinician: response.clinician,
});

export const ClinicianPortalAuthProvider = ({
  children,
}: ClinicianPortalAuthProviderProps) => {
  const queryClient = useQueryClient();

  const [
    session,
    setSession,
  ] = useState<ClinicianPortalContext | null>(
    null
  );

  const [
    isLoading,
    setIsLoading,
  ] = useState(true);

  const clearLocalSession = (): void => {
    clearClinicianPortalSession();
    setSession(null);

    queryClient.removeQueries({
      queryKey: ['clinician-portal'],
    });
  };

  const refresh = async (): Promise<void> => {
    const restored =
      readClinicianPortalSession();

    if (
      !restored
      || clinicianPortalSessionExpired(restored)
    ) {
      clearLocalSession();
      return;
    }

    const current =
      await fetchClinicianPortalMe();

    setSession(current);
  };

  useEffect(() => {
    let active = true;

    const restore = async (): Promise<void> => {
      const restored =
        readClinicianPortalSession();

      if (
        !restored
        || clinicianPortalSessionExpired(restored)
      ) {
        clearClinicianPortalSession();

        if (active) {
          setSession(null);
          setIsLoading(false);
        }

        return;
      }

      try {
        const current =
          await fetchClinicianPortalMe();

        if (active) {
          setSession(current);
        }
      } catch {
        clearClinicianPortalSession();

        if (active) {
          setSession(null);
        }
      } finally {
        if (active) {
          setIsLoading(false);
        }
      }
    };

    void restore();

    return () => {
      active = false;
    };
  }, []);

  const login = async (
    domain: string,
    email: string,
    password: string
  ): Promise<ClinicianPortalLoginResponse> => {
    const normalizedDomain =
      domain.trim().toLowerCase();

    setIsLoading(true);

    try {
      clearLocalSession();

      const response =
        await loginClinicianPortal(
          normalizedDomain,
          email.trim().toLowerCase(),
          password
        );

      writeClinicianPortalSession({
        token: response.token,
        domain: normalizedDomain,
        expiresAt: response.expires_at,
      });

      const current =
        contextFromLogin(response);

      setSession(current);

      return response;
    } finally {
      setIsLoading(false);
    }
  };

  const logout = async (): Promise<void> => {
    try {
      await logoutClinicianPortal();
    } catch {
      // Local revocation still clears browser access.
    } finally {
      clearLocalSession();
    }
  };

  const value = useMemo(
    (): ClinicianPortalAuthContextValue => ({
      session,
      isLoading,
      isAuthenticated: Boolean(session),
      login,
      logout,
      refresh,
    }),
    [
      session,
      isLoading,
    ]
  );

  return (
    <ClinicianPortalAuthContext.Provider
      value={value}
    >
      {children}
    </ClinicianPortalAuthContext.Provider>
  );
};

export const useClinicianPortalAuth =
  (): ClinicianPortalAuthContextValue => {
    const context =
      useContext(ClinicianPortalAuthContext);

    if (!context) {
      throw new Error(
        'useClinicianPortalAuth must be used within ClinicianPortalAuthProvider'
      );
    }

    return context;
  };
