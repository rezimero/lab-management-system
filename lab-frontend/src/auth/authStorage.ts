import {
  Tenant,
  User,
} from '../types';

import {
  StoredAuthentication,
  StoredPlatformSession,
} from './authTypes';

export const activeAuthenticationKeys = [
  'token',
  'user',
  'tenant',
  'tenant_domain',
  'is_superadmin',
  'is_impersonating',
  'role',
] as const;

export const platformStorageKeys = [
  'rezhel_platform_token',
  'rezhel_platform_user',
] as const;

export const authenticationStorageKeys = [
  ...activeAuthenticationKeys,
  ...platformStorageKeys,
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

export const clearActiveAuthentication =
  (): void => {
    activeAuthenticationKeys.forEach(
      (key) => {
        localStorage.removeItem(key);
      }
    );
  };

export const clearPlatformSession =
  (): void => {
    platformStorageKeys.forEach(
      (key) => {
        localStorage.removeItem(key);
      }
    );
  };

export const clearAllAuthentication =
  (): void => {
    clearActiveAuthentication();
    clearPlatformSession();
  };

export const readStoredAuthentication =
  (): StoredAuthentication => {
    return {
      token:
        localStorage.getItem('token'),

      user:
        parseStoredValue<User>(
          localStorage.getItem('user')
        ),

      tenant:
        parseStoredValue<Tenant>(
          localStorage.getItem('tenant')
        ),

      isSuperadmin:
        localStorage.getItem(
          'is_superadmin'
        ) === 'true',

      isImpersonating:
        localStorage.getItem(
          'is_impersonating'
        ) === 'true',
    };
  };

export const readPlatformSession =
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

export const readActiveToken =
  (): string | null => {
    return localStorage.getItem('token');
  };

export const readTenantDomain =
  (): string | null => {
    const storedDomain =
      localStorage.getItem(
        'tenant_domain'
      );

    if (storedDomain) {
      return storedDomain;
    }

    const storedTenant =
      localStorage.getItem('tenant');

    if (!storedTenant) {
      return null;
    }

    try {
      const tenant = JSON.parse(
        storedTenant
      ) as {
        domain?: unknown;
      };

      if (
        typeof tenant.domain !==
          'string' ||
        tenant.domain.trim() === ''
      ) {
        return null;
      }

      const domain = tenant.domain
        .trim()
        .toLowerCase();

      persistTenantDomain(domain);

      return domain;
    } catch {
      removeStoredTenant();

      return null;
    }
  };

export const persistPlatformAuthentication = (
  user: User,
  token: string
): void => {
  clearActiveAuthentication();

  localStorage.setItem(
    'token',
    token
  );

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

export const persistTenantAuthentication = (
  user: User,
  tenant: Tenant,
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
    JSON.stringify(user)
  );

  localStorage.setItem(
    'tenant',
    JSON.stringify(tenant)
  );

  localStorage.setItem(
    'tenant_domain',
    tenant.domain
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
    user.role
  );
};

export const persistPlatformBackup = (
  user: User,
  token: string
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

export const persistTenantDomain = (
  domain: string
): void => {
  localStorage.setItem(
    'tenant_domain',
    domain
  );
};

export const removeStoredTenant =
  (): void => {
    localStorage.removeItem('tenant');
    localStorage.removeItem(
      'tenant_domain'
    );
  };

export const persistActiveUser = (
  user: User
): void => {
  localStorage.setItem(
    'user',
    JSON.stringify(user)
  );
};
