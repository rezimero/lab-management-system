import {
  act,
  render,
  waitFor,
} from '@testing-library/react';

import {
  beforeEach,
  describe,
  expect,
  it,
  vi,
} from 'vitest';

import api from '../api/client';

import {
  AuthResponse,
  LoginCredentials,
  RegisterData,
  Tenant,
  User,
} from '../types';

import {
  AuthContextType,
  AuthProvider,
  useAuth,
} from './AuthContext';

vi.mock('../api/client', () => ({
  default: {
    get: vi.fn(),
    post: vi.fn(),
  },
}));

const mockedGet = vi.mocked(api.get);
const mockedPost = vi.mocked(api.post);

const tenantUser: User = {
  id: 11,
  name: 'Tenant Administrator',
  email: 'tenant-admin@example.test',
  role: 'admin',
  is_active: true,
  created_at: '2026-07-20T08:00:00.000Z',
  updated_at: '2026-07-20T08:00:00.000Z',
};

const updatedTenantUser: User = {
  ...tenantUser,
  name: 'Updated Tenant Administrator',
  updated_at: '2026-07-23T08:00:00.000Z',
};

const superadminUser: User = {
  id: 1,
  name: 'Platform Superadmin',
  email: 'superadmin@example.test',
  role: 'superadmin',
  is_active: true,
  created_at: '2026-07-01T08:00:00.000Z',
  updated_at: '2026-07-01T08:00:00.000Z',
};

const tenant: Tenant = {
  id: 7,
  name: 'Characterization Laboratory',
  domain: 'characterization-lab',
  database: 'characterization_tenant_test',
  is_active: true,
  trial_ends_at: null,
  created_at: '2026-07-10T08:00:00.000Z',
  updated_at: '2026-07-10T08:00:00.000Z',
};

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

const allAuthenticationKeys = [
  ...activeAuthenticationKeys,
  ...platformStorageKeys,
] as const;

let currentAuth: AuthContextType | null = null;

const AuthProbe = () => {
  currentAuth = useAuth();

  return null;
};

const OutsideProviderProbe = () => {
  useAuth();

  return null;
};

const getAuth = (): AuthContextType => {
  if (!currentAuth) {
    throw new Error('The authentication context is not ready.');
  }

  return currentAuth;
};

const renderProvider = async (): Promise<void> => {
  render(
    <AuthProvider>
      <AuthProbe />
    </AuthProvider>
  );

  await waitFor(() => {
    expect(getAuth().isLoading).toBe(false);
  });
};

const storeActiveTenantSession = (
  options: {
    impersonating?: boolean;
    sessionTenant?: Tenant;
    sessionToken?: string;
    sessionUser?: User;
  } = {}
): void => {
  const {
    impersonating = false,
    sessionTenant = tenant,
    sessionToken = 'tenant-token',
    sessionUser = tenantUser,
  } = options;

  localStorage.setItem('token', sessionToken);
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
};

const storePlatformSession = (
  token = 'platform-token',
  user = superadminUser
): void => {
  localStorage.setItem(
    'rezhel_platform_token',
    token
  );
  localStorage.setItem(
    'rezhel_platform_user',
    JSON.stringify(user)
  );
};

const storeActiveSuperadminSession = (
  token = 'platform-token',
  user = superadminUser
): void => {
  localStorage.setItem('token', token);
  localStorage.setItem(
    'user',
    JSON.stringify(user)
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
};

const resolvePostWith = (
  data: unknown
): void => {
  mockedPost.mockResolvedValueOnce({
    data,
  } as never);
};

const resolveGetWith = (
  data: unknown
): void => {
  mockedGet.mockResolvedValueOnce({
    data,
  } as never);
};

const expectStorageCleared = (): void => {
  allAuthenticationKeys.forEach((key) => {
    expect(localStorage.getItem(key)).toBeNull();
  });
};

describe('AuthProvider characterization', () => {
  beforeEach(() => {
    currentAuth = null;
    localStorage.clear();
    vi.clearAllMocks();
  });

  it('starts unauthenticated when storage is empty', async () => {
    await renderProvider();

    expect(getAuth().user).toBeNull();
    expect(getAuth().tenant).toBeNull();
    expect(getAuth().impersonator).toBeNull();
    expect(getAuth().isAuthenticated).toBe(false);
    expect(getAuth().isSuperadmin).toBe(false);
    expect(getAuth().isImpersonating).toBe(false);

    expectStorageCleared();
  });

  it('restores a valid tenant session and tenant domain', async () => {
    localStorage.removeItem('tenant_domain');
    storeActiveTenantSession();
    localStorage.removeItem('tenant_domain');

    await renderProvider();

    expect(getAuth().user).toEqual(tenantUser);
    expect(getAuth().tenant).toEqual(tenant);
    expect(getAuth().impersonator).toBeNull();
    expect(getAuth().isAuthenticated).toBe(true);
    expect(getAuth().isSuperadmin).toBe(false);
    expect(getAuth().isImpersonating).toBe(false);

    expect(
      localStorage.getItem('tenant_domain')
    ).toBe(tenant.domain);
  });

  it('restores a valid landlord superadmin session', async () => {
    storeActiveSuperadminSession();

    localStorage.setItem(
      'tenant',
      JSON.stringify(tenant)
    );
    localStorage.setItem(
      'tenant_domain',
      tenant.domain
    );

    await renderProvider();

    expect(getAuth().user).toEqual(superadminUser);
    expect(getAuth().tenant).toBeNull();
    expect(getAuth().impersonator).toBeNull();
    expect(getAuth().isAuthenticated).toBe(true);
    expect(getAuth().isSuperadmin).toBe(true);
    expect(getAuth().isImpersonating).toBe(false);

    expect(localStorage.getItem('tenant')).toBeNull();
    expect(
      localStorage.getItem('tenant_domain')
    ).toBeNull();
  });

  it('restores an impersonated tenant and platform identity', async () => {
    storeActiveTenantSession({
      impersonating: true,
      sessionToken: 'temporary-tenant-token',
    });
    storePlatformSession();

    await renderProvider();

    expect(getAuth().user).toEqual(tenantUser);
    expect(getAuth().tenant).toEqual(tenant);
    expect(getAuth().impersonator).toEqual(
      superadminUser
    );
    expect(getAuth().isAuthenticated).toBe(true);
    expect(getAuth().isSuperadmin).toBe(false);
    expect(getAuth().isImpersonating).toBe(true);

    expect(
      localStorage.getItem('tenant_domain')
    ).toBe(tenant.domain);
  });

  it('clears malformed stored authentication data', async () => {
    localStorage.setItem('token', 'stale-token');
    localStorage.setItem('user', '{invalid-json');
    localStorage.setItem(
      'tenant',
      JSON.stringify(tenant)
    );
    localStorage.setItem(
      'is_superadmin',
      'false'
    );
    localStorage.setItem(
      'is_impersonating',
      'false'
    );
    storePlatformSession();

    await renderProvider();

    expect(getAuth().isAuthenticated).toBe(false);
    expect(getAuth().user).toBeNull();
    expect(getAuth().tenant).toBeNull();

    expectStorageCleared();
  });

  it('invalidates a tenant session without a tenant domain', async () => {
    storeActiveTenantSession({
      sessionTenant: {
        ...tenant,
        domain: '',
      },
    });

    await renderProvider();

    expect(getAuth().isAuthenticated).toBe(false);
    expect(getAuth().user).toBeNull();
    expect(getAuth().tenant).toBeNull();

    expectStorageCleared();
  });

  it('persists a tenant login response', async () => {
    const credentials: LoginCredentials = {
      domain: tenant.domain,
      email: tenantUser.email,
      password: 'TenantPassword123!',
    };

    const response: AuthResponse = {
      message: 'Login successful',
      user: tenantUser,
      token: 'new-tenant-token',
      tenant,
      is_superadmin: false,
      guard: 'tenant',
    };

    resolvePostWith(response);

    await renderProvider();

    let result: AuthResponse | undefined;

    await act(async () => {
      result = await getAuth().login(credentials);
    });

    expect(result).toEqual(response);
    expect(mockedPost).toHaveBeenCalledWith(
      '/login',
      credentials
    );

    expect(getAuth().user).toEqual(tenantUser);
    expect(getAuth().tenant).toEqual(tenant);
    expect(getAuth().isAuthenticated).toBe(true);
    expect(getAuth().isSuperadmin).toBe(false);
    expect(getAuth().isImpersonating).toBe(false);

    expect(localStorage.getItem('token')).toBe(
      'new-tenant-token'
    );
    expect(
      JSON.parse(
        localStorage.getItem('user') ?? 'null'
      )
    ).toEqual(tenantUser);
    expect(
      JSON.parse(
        localStorage.getItem('tenant') ?? 'null'
      )
    ).toEqual(tenant);
    expect(
      localStorage.getItem('tenant_domain')
    ).toBe(tenant.domain);
    expect(
      localStorage.getItem('is_superadmin')
    ).toBe('false');
    expect(
      localStorage.getItem('is_impersonating')
    ).toBe('false');
    expect(localStorage.getItem('role')).toBe(
      tenantUser.role
    );
  });

  it('persists a superadmin login without tenant state', async () => {
    const credentials: LoginCredentials = {
      email: superadminUser.email,
      password: 'PlatformPassword123!',
    };

    const response: AuthResponse = {
      message: 'Login successful',
      user: superadminUser,
      token: 'new-platform-token',
      is_superadmin: true,
      guard: 'landlord',
    };

    localStorage.setItem(
      'tenant',
      JSON.stringify(tenant)
    );
    localStorage.setItem(
      'tenant_domain',
      tenant.domain
    );

    resolvePostWith(response);

    await renderProvider();

    await act(async () => {
      await getAuth().login(credentials);
    });

    expect(mockedPost).toHaveBeenCalledWith(
      '/login',
      credentials
    );

    expect(getAuth().user).toEqual(superadminUser);
    expect(getAuth().tenant).toBeNull();
    expect(getAuth().isAuthenticated).toBe(true);
    expect(getAuth().isSuperadmin).toBe(true);
    expect(getAuth().isImpersonating).toBe(false);

    expect(localStorage.getItem('token')).toBe(
      'new-platform-token'
    );
    expect(localStorage.getItem('tenant')).toBeNull();
    expect(
      localStorage.getItem('tenant_domain')
    ).toBeNull();
    expect(
      localStorage.getItem('is_superadmin')
    ).toBe('true');
    expect(
      localStorage.getItem('is_impersonating')
    ).toBe('false');
    expect(localStorage.getItem('role')).toBe(
      'superadmin'
    );
  });

  it('persists a newly registered tenant session', async () => {
    const registration: RegisterData = {
      name: tenantUser.name,
      email: tenantUser.email,
      password: 'TenantPassword123!',
      password_confirmation: 'TenantPassword123!',
      tenant_name: tenant.name,
      tenant_domain: tenant.domain,
    };

    const response: AuthResponse = {
      message: 'Registration successful',
      user: tenantUser,
      token: 'registration-token',
      tenant,
      is_superadmin: false,
      guard: 'tenant',
    };

    resolvePostWith(response);

    await renderProvider();

    await act(async () => {
      await getAuth().register(registration);
    });

    expect(mockedPost).toHaveBeenCalledWith(
      '/register',
      registration
    );

    expect(getAuth().user).toEqual(tenantUser);
    expect(getAuth().tenant).toEqual(tenant);
    expect(getAuth().isAuthenticated).toBe(true);
    expect(getAuth().isSuperadmin).toBe(false);

    expect(localStorage.getItem('token')).toBe(
      'registration-token'
    );
    expect(
      localStorage.getItem('tenant_domain')
    ).toBe(tenant.domain);
  });

  it('starts impersonation while preserving the platform session', async () => {
    storeActiveSuperadminSession();

    resolvePostWith({
      message: `Impersonating tenant: ${tenant.name}`,
      token: 'temporary-tenant-token',
      is_impersonating: true,
      user: {
        id: tenantUser.id,
        name: tenantUser.name,
        email: tenantUser.email,
        role: tenantUser.role,
      },
      tenant: {
        id: tenant.id,
        name: tenant.name,
        domain: tenant.domain,
      },
    });

    await renderProvider();

    await act(async () => {
      await getAuth().startImpersonation(tenant.id);
    });

    expect(mockedPost).toHaveBeenCalledWith(
      `/landlord/impersonate/${tenant.id}`
    );

    expect(getAuth().user).toEqual({
      ...tenantUser,
      created_at: '',
      updated_at: '',
    });
    expect(getAuth().tenant).toEqual({
      ...tenant,
      database: '',
      created_at: '',
      updated_at: '',
    });
    expect(getAuth().impersonator).toEqual(
      superadminUser
    );
    expect(getAuth().isSuperadmin).toBe(false);
    expect(getAuth().isImpersonating).toBe(true);

    expect(
      localStorage.getItem('rezhel_platform_token')
    ).toBe('platform-token');
    expect(
      JSON.parse(
        localStorage.getItem(
          'rezhel_platform_user'
        ) ?? 'null'
      )
    ).toEqual(superadminUser);

    expect(localStorage.getItem('token')).toBe(
      'temporary-tenant-token'
    );
    expect(
      localStorage.getItem('tenant_domain')
    ).toBe(tenant.domain);
    expect(
      localStorage.getItem('is_impersonating')
    ).toBe('true');
  });

  it('stops impersonation after an expired temporary token', async () => {
    storeActiveTenantSession({
      impersonating: true,
      sessionToken: 'expired-temporary-token',
    });
    storePlatformSession();

    mockedPost
      .mockRejectedValueOnce(
        new Error('Temporary token expired')
      )
      .mockResolvedValueOnce({
        data: {
          message: 'Stopped impersonating',
        },
      } as never);

    await renderProvider();

    await act(async () => {
      await getAuth().stopImpersonation();
    });

    expect(mockedPost).toHaveBeenNthCalledWith(
      1,
      '/logout'
    );
    expect(mockedPost).toHaveBeenNthCalledWith(
      2,
      '/landlord/impersonate/stop'
    );

    expect(getAuth().user).toEqual(superadminUser);
    expect(getAuth().tenant).toBeNull();
    expect(getAuth().impersonator).toBeNull();
    expect(getAuth().isAuthenticated).toBe(true);
    expect(getAuth().isSuperadmin).toBe(true);
    expect(getAuth().isImpersonating).toBe(false);

    expect(localStorage.getItem('token')).toBe(
      'platform-token'
    );
    expect(localStorage.getItem('tenant')).toBeNull();
    expect(
      localStorage.getItem('tenant_domain')
    ).toBeNull();
    expect(
      localStorage.getItem('rezhel_platform_token')
    ).toBeNull();
    expect(
      localStorage.getItem('rezhel_platform_user')
    ).toBeNull();
  });

  it('clears local state when a normal logout request fails', async () => {
    storeActiveTenantSession();

    mockedPost.mockRejectedValueOnce(
      new Error('Logout request failed')
    );

    await renderProvider();

    await act(async () => {
      await getAuth().logout();
    });

    expect(mockedPost).toHaveBeenCalledTimes(1);
    expect(mockedPost).toHaveBeenCalledWith(
      '/logout'
    );

    expect(getAuth().user).toBeNull();
    expect(getAuth().tenant).toBeNull();
    expect(getAuth().impersonator).toBeNull();
    expect(getAuth().isAuthenticated).toBe(false);
    expect(getAuth().isSuperadmin).toBe(false);
    expect(getAuth().isImpersonating).toBe(false);

    expectStorageCleared();
  });

  it('attempts both token revocations during impersonated logout', async () => {
    storeActiveTenantSession({
      impersonating: true,
      sessionToken: 'temporary-tenant-token',
    });
    storePlatformSession();

    mockedPost
      .mockRejectedValueOnce(
        new Error('Temporary token expired')
      )
      .mockResolvedValueOnce({
        data: {
          message: 'Logged out',
        },
      } as never);

    await renderProvider();

    await act(async () => {
      await getAuth().logout();
    });

    expect(mockedPost).toHaveBeenCalledTimes(2);
    expect(mockedPost).toHaveBeenNthCalledWith(
      1,
      '/logout'
    );
    expect(mockedPost).toHaveBeenNthCalledWith(
      2,
      '/logout'
    );

    expect(getAuth().isAuthenticated).toBe(false);
    expect(getAuth().user).toBeNull();
    expect(getAuth().tenant).toBeNull();
    expect(getAuth().impersonator).toBeNull();

    expectStorageCleared();
  });

  it('refreshes the active user in state and storage', async () => {
    storeActiveTenantSession();

    resolveGetWith({
      user: updatedTenantUser,
    });

    await renderProvider();

    await act(async () => {
      await getAuth().refreshUser();
    });

    expect(mockedGet).toHaveBeenCalledWith('/user');
    expect(getAuth().user).toEqual(
      updatedTenantUser
    );
    expect(getAuth().tenant).toEqual(tenant);

    expect(
      JSON.parse(
        localStorage.getItem('user') ?? 'null'
      )
    ).toEqual(updatedTenantUser);
  });

  it('throws when useAuth is called outside AuthProvider', () => {
    const consoleError = vi
      .spyOn(console, 'error')
      .mockImplementation(() => undefined);

    expect(() => {
      render(<OutsideProviderProbe />);
    }).toThrow(
      'useAuth must be used within AuthProvider'
    );

    consoleError.mockRestore();
  });
});
