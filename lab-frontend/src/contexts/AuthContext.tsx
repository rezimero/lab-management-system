import {
  createContext,
  ReactNode,
  useContext,
  useEffect,
  useState,
} from 'react';

import api from '../api/client';

import {
  normaliseImpersonatedTenant,
  normaliseImpersonatedUser,
} from '../auth/authSessionMapper';

import {
  clearAllAuthentication,
  clearPlatformSession,
  persistActiveUser,
  persistPlatformAuthentication,
  persistPlatformBackup,
  persistTenantAuthentication,
  persistTenantDomain,
  readActiveToken,
  readPlatformSession,
  readStoredAuthentication,
  removeStoredTenant,
} from '../auth/authStorage';

import { ImpersonationResponse } from '../auth/authTypes';

import {
  AuthResponse,
  LoginCredentials,
  RegisterData,
  Tenant,
  User,
} from '../types';

export interface AuthContextType {
  user: User | null;
  tenant: Tenant | null;
  impersonator: User | null;

  isLoading: boolean;
  isSuperadmin: boolean;
  isImpersonating: boolean;
  isAuthenticated: boolean;

  login: (
    credentials: LoginCredentials
  ) => Promise<AuthResponse>;

  register: (
    data: RegisterData
  ) => Promise<void>;

  startImpersonation: (
    tenantId: number
  ) => Promise<void>;

  stopImpersonation:
    () => Promise<void>;

  logout: () => Promise<void>;

  refreshUser: () => Promise<void>;
}

const AuthContext = createContext<
  AuthContextType | undefined
>(undefined);

interface AuthProviderProps {
  children: ReactNode;
}

export const AuthProvider = ({
  children,
}: AuthProviderProps) => {
  const [
    user,
    setUser,
  ] = useState<User | null>(null);

  const [
    tenant,
    setTenant,
  ] = useState<Tenant | null>(null);

  const [
    impersonator,
    setImpersonator,
  ] = useState<User | null>(null);

  const [
    isLoading,
    setIsLoading,
  ] = useState(true);

  const [
    isSuperadmin,
    setIsSuperadmin,
  ] = useState(false);

  const [
    isImpersonating,
    setIsImpersonating,
  ] = useState(false);

  const resetState = (): void => {
    setUser(null);
    setTenant(null);
    setImpersonator(null);
    setIsSuperadmin(false);
    setIsImpersonating(false);
  };

  const applyPlatformSession = (
    sessionUser: User,
    token: string
  ): void => {
    persistPlatformAuthentication(
      sessionUser,
      token
    );

    setUser(sessionUser);
    setTenant(null);
    setIsSuperadmin(true);
    setIsImpersonating(false);
    setImpersonator(null);
  };

  const applyTenantSession = (
    sessionUser: User,
    sessionTenant: Tenant,
    token: string,
    impersonating: boolean
  ): void => {
    persistTenantAuthentication(
      sessionUser,
      sessionTenant,
      token,
      impersonating
    );

    setUser(sessionUser);
    setTenant(sessionTenant);
    setIsSuperadmin(false);
    setIsImpersonating(
      impersonating
    );
  };

  useEffect(() => {
    const restoreSession = (): void => {
      const stored =
        readStoredAuthentication();

      if (!stored.token || !stored.user) {
        clearAllAuthentication();
        resetState();
        setIsLoading(false);

        return;
      }

      if (stored.isImpersonating) {
        const platformSession =
          readPlatformSession();

        if (
          !platformSession ||
          !stored.tenant?.domain
        ) {
          clearAllAuthentication();
          resetState();
          setIsLoading(false);

          return;
        }

        persistTenantDomain(
          stored.tenant.domain
        );

        setUser(stored.user);
        setTenant(stored.tenant);
        setImpersonator(
          platformSession.user
        );
        setIsSuperadmin(false);
        setIsImpersonating(true);
        setIsLoading(false);

        return;
      }

      if (stored.isSuperadmin) {
        removeStoredTenant();

        setUser(stored.user);
        setTenant(null);
        setImpersonator(null);
        setIsSuperadmin(true);
        setIsImpersonating(false);
        setIsLoading(false);

        return;
      }

      if (!stored.tenant?.domain) {
        clearAllAuthentication();
        resetState();
        setIsLoading(false);

        return;
      }

      persistTenantDomain(
        stored.tenant.domain
      );

      setUser(stored.user);
      setTenant(stored.tenant);
      setImpersonator(null);
      setIsSuperadmin(false);
      setIsImpersonating(false);
      setIsLoading(false);
    };

    restoreSession();
  }, []);

  const login = async (
    credentials: LoginCredentials
  ): Promise<AuthResponse> => {
    setIsLoading(true);

    try {
      clearAllAuthentication();

      const response =
        await api.post<AuthResponse>(
          '/login',
          credentials
        );

      const {
        user: sessionUser,
        token,
        tenant: sessionTenant,
        is_superadmin: sessionIsSuperadmin,
      } = response.data;

      if (sessionIsSuperadmin) {
        applyPlatformSession(
          sessionUser,
          token
        );
      } else {
        if (!sessionTenant?.domain) {
          throw new Error(
            'The laboratory session does not contain a tenant domain.'
          );
        }

        applyTenantSession(
          sessionUser,
          sessionTenant,
          token,
          false
        );
      }

      return response.data;
    } finally {
      setIsLoading(false);
    }
  };

  const register = async (
    data: RegisterData
  ): Promise<void> => {
    setIsLoading(true);

    try {
      clearAllAuthentication();

      const response =
        await api.post<AuthResponse>(
          '/register',
          data
        );

      const {
        user: sessionUser,
        token,
        tenant: sessionTenant,
      } = response.data;

      if (!sessionTenant?.domain) {
        throw new Error(
          'The newly registered laboratory does not contain a tenant domain.'
        );
      }

      applyTenantSession(
        sessionUser,
        sessionTenant,
        token,
        false
      );
    } finally {
      setIsLoading(false);
    }
  };

  const startImpersonation = async (
    tenantId: number
  ): Promise<void> => {
    if (
      !isSuperadmin ||
      !user ||
      user.role !== 'superadmin'
    ) {
      throw new Error(
        'Only a landlord superadmin can enter a tenant maintenance session.'
      );
    }

    if (isImpersonating) {
      throw new Error(
        'A tenant maintenance session is already active.'
      );
    }

    const platformToken =
      readActiveToken();

    if (!platformToken) {
      throw new Error(
        'The landlord session is no longer available.'
      );
    }

    const response =
      await api.post<ImpersonationResponse>(
        `/landlord/impersonate/${tenantId}`
      );

    const impersonatedUser =
      normaliseImpersonatedUser(
        response.data.user
      );

    const impersonatedTenant =
      normaliseImpersonatedTenant(
        response.data.tenant
      );

    if (
      !impersonatedTenant.domain ||
      !response.data.token
    ) {
      throw new Error(
        'The tenant maintenance session could not be established.'
      );
    }

    persistPlatformBackup(
      user,
      platformToken
    );

    applyTenantSession(
      impersonatedUser,
      impersonatedTenant,
      response.data.token,
      true
    );

    setImpersonator(user);
  };

  const stopImpersonation =
    async (): Promise<void> => {
      if (!isImpersonating) {
        return;
      }

      const platformSession =
        readPlatformSession();

      if (!platformSession) {
        clearAllAuthentication();
        resetState();

        throw new Error(
          'The landlord session could not be restored. Sign in again.'
        );
      }

      /*
       * Revoke the temporary tenant token first.
       */
      try {
        await api.post('/logout');
      } catch {
        // The token may already have expired.
      }

      applyPlatformSession(
        platformSession.user,
        platformSession.token
      );

      /*
       * Retain compatibility with the existing
       * backend impersonation endpoint.
       */
      try {
        await api.post(
          '/landlord/impersonate/stop'
        );
      } catch {
        // Local restoration is authoritative.
      }

      clearPlatformSession();
    };

  const logout =
    async (): Promise<void> => {
      if (isImpersonating) {
        const platformSession =
          readPlatformSession();

        try {
          await api.post('/logout');
        } catch {
          // Temporary token may already be invalid.
        }

        if (platformSession) {
          applyPlatformSession(
            platformSession.user,
            platformSession.token
          );

          try {
            await api.post('/logout');
          } catch {
            // Landlord token may already be invalid.
          }
        }
      } else {
        try {
          await api.post('/logout');
        } catch {
          // Local logout must still complete.
        }
      }

      clearAllAuthentication();
      resetState();
    };

  const refreshUser =
    async (): Promise<void> => {
      const response =
        await api.get<{
          user: User;
        }>('/user');

      persistActiveUser(
        response.data.user
      );

      setUser(response.data.user);
    };

  return (
    <AuthContext.Provider
      value={{
        user,
        tenant,
        impersonator,
        isLoading,
        isSuperadmin,
        isImpersonating,
        isAuthenticated:
          Boolean(user),
        login,
        register,
        startImpersonation,
        stopImpersonation,
        logout,
        refreshUser,
      }}
    >
      {children}
    </AuthContext.Provider>
  );
};

export const useAuth =
  (): AuthContextType => {
    const context =
      useContext(AuthContext);

    if (!context) {
      throw new Error(
        'useAuth must be used within AuthProvider'
      );
    }

    return context;
  };
