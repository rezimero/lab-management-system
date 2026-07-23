import {
  createContext,
  ReactNode,
  useContext,
  useEffect,
  useState,
} from 'react';

import api from '../api/client';

import {
  AuthResponse,
  LoginCredentials,
  RegisterData,
  Tenant,
  User,
} from '../types';

interface ImpersonationResponse {
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

interface StoredPlatformSession {
  token: string;
  user: User;
}

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

const activeAuthenticationKeys = [
  'token',
  'user',
  'tenant',
  'tenant_domain',
  'is_superadmin',
  'is_impersonating',
  'role',
] as const;

const platformStorageKeys = [
  'rezhel_platform_token',
  'rezhel_platform_user',
] as const;

const parseStoredValue = <T,>(
  value: string | null
): T | null => {
  if (!value) {
    return null;
  }

  try {
    return JSON.parse(value) as T;
  } catch {
    return null;
  }
};

const normaliseUser = (
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

const normaliseTenant = (
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

const clearActiveAuthentication =
  (): void => {
    activeAuthenticationKeys.forEach(
      (key) => {
        localStorage.removeItem(key);
      }
    );
  };

const clearPlatformSession =
  (): void => {
    platformStorageKeys.forEach(
      (key) => {
        localStorage.removeItem(key);
      }
    );
  };

const clearAllAuthentication =
  (): void => {
    clearActiveAuthentication();
    clearPlatformSession();
  };

const readPlatformSession =
  (): StoredPlatformSession | null => {
    const token =
      localStorage.getItem(
        'rezhel_platform_token'
      );

    const user = parseStoredValue<User>(
      localStorage.getItem(
        'rezhel_platform_user'
      )
    );

    if (
      !token ||
      !user ||
      user.role !== 'superadmin'
    ) {
      return null;
    }

    return {
      token,
      user,
    };
  };

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

  const persistPlatformSession = (
    sessionUser: User,
    token: string
  ): void => {
    clearActiveAuthentication();

    localStorage.setItem(
      'token',
      token
    );

    localStorage.setItem(
      'user',
      JSON.stringify(sessionUser)
    );

    localStorage.setItem(
      'is_superadmin',
      'true'
    );

    localStorage.setItem(
      'is_impersonating',
      'false'
    );

    localStorage.setItem(
      'role',
      'superadmin'
    );

    setUser(sessionUser);
    setTenant(null);
    setIsSuperadmin(true);
    setIsImpersonating(false);
    setImpersonator(null);
  };

  const persistTenantSession = (
    sessionUser: User,
    sessionTenant: Tenant,
    token: string,
    impersonating: boolean
  ): void => {
    clearActiveAuthentication();

    localStorage.setItem(
      'token',
      token
    );

    localStorage.setItem(
      'user',
      JSON.stringify(sessionUser)
    );

    localStorage.setItem(
      'tenant',
      JSON.stringify(sessionTenant)
    );

    localStorage.setItem(
      'tenant_domain',
      sessionTenant.domain
    );

    localStorage.setItem(
      'is_superadmin',
      'false'
    );

    localStorage.setItem(
      'is_impersonating',
      String(impersonating)
    );

    localStorage.setItem(
      'role',
      sessionUser.role
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
      const token =
        localStorage.getItem('token');

      const storedUser =
        parseStoredValue<User>(
          localStorage.getItem('user')
        );

      const storedTenant =
        parseStoredValue<Tenant>(
          localStorage.getItem('tenant')
        );

      const storedIsSuperadmin =
        localStorage.getItem(
          'is_superadmin'
        ) === 'true';

      const storedIsImpersonating =
        localStorage.getItem(
          'is_impersonating'
        ) === 'true';

      if (!token || !storedUser) {
        clearAllAuthentication();
        resetState();
        setIsLoading(false);

        return;
      }

      if (storedIsImpersonating) {
        const platformSession =
          readPlatformSession();

        if (
          !platformSession ||
          !storedTenant?.domain
        ) {
          clearAllAuthentication();
          resetState();
          setIsLoading(false);

          return;
        }

        localStorage.setItem(
          'tenant_domain',
          storedTenant.domain
        );

        setUser(storedUser);
        setTenant(storedTenant);
        setImpersonator(
          platformSession.user
        );
        setIsSuperadmin(false);
        setIsImpersonating(true);
        setIsLoading(false);

        return;
      }

      if (storedIsSuperadmin) {
        localStorage.removeItem('tenant');
        localStorage.removeItem(
          'tenant_domain'
        );

        setUser(storedUser);
        setTenant(null);
        setImpersonator(null);
        setIsSuperadmin(true);
        setIsImpersonating(false);
        setIsLoading(false);

        return;
      }

      if (!storedTenant?.domain) {
        clearAllAuthentication();
        resetState();
        setIsLoading(false);

        return;
      }

      localStorage.setItem(
        'tenant_domain',
        storedTenant.domain
      );

      setUser(storedUser);
      setTenant(storedTenant);
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
        persistPlatformSession(
          sessionUser,
          token
        );
      } else {
        if (!sessionTenant?.domain) {
          throw new Error(
            'The laboratory session does not contain a tenant domain.'
          );
        }

        persistTenantSession(
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

      persistTenantSession(
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
      localStorage.getItem('token');

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
      normaliseUser(
        response.data.user
      );

    const impersonatedTenant =
      normaliseTenant(
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

    localStorage.setItem(
      'rezhel_platform_token',
      platformToken
    );

    localStorage.setItem(
      'rezhel_platform_user',
      JSON.stringify(user)
    );

    persistTenantSession(
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

      persistPlatformSession(
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
          persistPlatformSession(
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

      localStorage.setItem(
        'user',
        JSON.stringify(
          response.data.user
        )
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