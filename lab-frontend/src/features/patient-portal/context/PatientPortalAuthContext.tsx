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
  fetchPatientPortalMe,
  loginPatientPortal,
  logoutPatientPortal,
} from '../api/patientPortalApi';

import {
  clearPatientPortalSession,
  patientPortalSessionExpired,
  readPatientPortalSession,
  writePatientPortalSession,
} from '../auth/patientPortalStorage';

import type {
  PatientPortalContext,
  PatientPortalLoginResponse,
} from '../types/patientPortal.types';

interface PatientPortalAuthContextValue {
  session: PatientPortalContext | null;
  isLoading: boolean;
  isAuthenticated: boolean;
  login: (
    domain: string,
    email: string,
    password: string
  ) => Promise<PatientPortalLoginResponse>;
  logout: () => Promise<void>;
  refresh: () => Promise<void>;
}

const PatientPortalAuthContext =
  createContext<
    PatientPortalAuthContextValue | undefined
  >(undefined);

interface PatientPortalAuthProviderProps {
  children: ReactNode;
}

const contextFromLogin = (
  response: PatientPortalLoginResponse
): PatientPortalContext => ({
  portal: response.portal,
  account: response.account,
  patient: response.patient,
});

export const PatientPortalAuthProvider = ({
  children,
}: PatientPortalAuthProviderProps) => {
  const queryClient = useQueryClient();

  const [
    session,
    setSession,
  ] = useState<PatientPortalContext | null>(
    null
  );

  const [
    isLoading,
    setIsLoading,
  ] = useState(true);

  const clearLocalSession = (): void => {
    clearPatientPortalSession();
    setSession(null);

    queryClient.removeQueries({
      queryKey: ['patient-portal'],
    });
  };

  const refresh = async (): Promise<void> => {
    const restored =
      readPatientPortalSession();

    if (
      !restored
      || patientPortalSessionExpired(restored)
    ) {
      clearLocalSession();
      return;
    }

    const current =
      await fetchPatientPortalMe();

    setSession(current);
  };

  useEffect(() => {
    let active = true;

    const restore = async (): Promise<void> => {
      const restored =
        readPatientPortalSession();

      if (
        !restored
        || patientPortalSessionExpired(restored)
      ) {
        clearPatientPortalSession();

        if (active) {
          setSession(null);
          setIsLoading(false);
        }

        return;
      }

      try {
        const current =
          await fetchPatientPortalMe();

        if (active) {
          setSession(current);
        }
      } catch {
        clearPatientPortalSession();

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
  ): Promise<PatientPortalLoginResponse> => {
    const normalizedDomain =
      domain.trim().toLowerCase();

    setIsLoading(true);

    try {
      clearLocalSession();

      const response =
        await loginPatientPortal(
          normalizedDomain,
          email.trim().toLowerCase(),
          password
        );

      writePatientPortalSession({
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
      await logoutPatientPortal();
    } catch {
      // Local revocation still clears browser access.
    } finally {
      clearLocalSession();
    }
  };

  const value = useMemo(
    (): PatientPortalAuthContextValue => ({
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
    <PatientPortalAuthContext.Provider
      value={value}
    >
      {children}
    </PatientPortalAuthContext.Provider>
  );
};

export const usePatientPortalAuth =
  (): PatientPortalAuthContextValue => {
    const context =
      useContext(PatientPortalAuthContext);

    if (!context) {
      throw new Error(
        'usePatientPortalAuth must be used within PatientPortalAuthProvider'
      );
    }

    return context;
  };
