import axios, {
  AxiosError,
  AxiosInstance,
  InternalAxiosRequestConfig,
} from 'axios';

const apiUrl =
  import.meta.env.VITE_API_URL?.trim() ||
  'http://127.0.0.1:8000/api';

const authenticationStorageKeys = [
  'token',
  'user',
  'tenant',
  'tenant_domain',
  'is_superadmin',
  'is_impersonating',
  'role',
  'rezhel_platform_token',
  'rezhel_platform_user',
] as const;

const clearAuthenticationStorage = (): void => {
  authenticationStorageKeys.forEach((key) => {
    localStorage.removeItem(key);
  });
};

const restoreTenantDomain = (): string | null => {
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
      typeof tenant.domain !== 'string' ||
      tenant.domain.trim() === ''
    ) {
      return null;
    }

    const domain = tenant.domain
      .trim()
      .toLowerCase();

    localStorage.setItem(
      'tenant_domain',
      domain
    );

    return domain;
  } catch {
    localStorage.removeItem('tenant');
    localStorage.removeItem(
      'tenant_domain'
    );

    return null;
  }
};

const api: AxiosInstance = axios.create({
  baseURL: apiUrl,

  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },

  withCredentials: false,
});

api.interceptors.request.use(
  (
    config: InternalAxiosRequestConfig
  ): InternalAxiosRequestConfig => {
    const token =
      localStorage.getItem('token');

    const isSuperadmin =
      localStorage.getItem(
        'is_superadmin'
      ) === 'true';

    if (token) {
      config.headers.Authorization =
        `Bearer ${token}`;
    } else {
      delete config.headers.Authorization;
    }

    if (isSuperadmin) {
      delete config.headers[
        'X-Tenant-Domain'
      ];

      return config;
    }

    const tenantDomain =
      localStorage.getItem(
        'tenant_domain'
      ) ||
      restoreTenantDomain();

    if (tenantDomain) {
      config.headers[
        'X-Tenant-Domain'
      ] = tenantDomain;
    } else {
      delete config.headers[
        'X-Tenant-Domain'
      ];
    }

    return config;
  },

  (error: AxiosError) =>
    Promise.reject(error)
);

api.interceptors.response.use(
  (response) => response,

  (error: AxiosError) => {
    if (error.response?.status === 401) {
      const requestUrl =
        error.config?.url ?? '';

      const isAuthenticationRequest =
        requestUrl.endsWith('/login') ||
        requestUrl.endsWith('/register');

      const isImpersonationLogout =
        requestUrl.endsWith('/logout') &&
        localStorage.getItem(
          'is_impersonating'
        ) === 'true';

      /*
       * During impersonation shutdown, AuthContext
       * must be allowed to restore the landlord
       * session even when the temporary tenant token
       * has already expired.
       */
      if (isImpersonationLogout) {
        return Promise.reject(error);
      }

      clearAuthenticationStorage();

      if (
        !isAuthenticationRequest &&
        window.location.pathname !==
          '/login'
      ) {
        window.location.assign('/login');
      }
    }

    return Promise.reject(error);
  }
);

export default api;