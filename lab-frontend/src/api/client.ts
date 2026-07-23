import axios, {
  AxiosError,
  AxiosInstance,
  InternalAxiosRequestConfig,
} from 'axios';

import {
  clearAllAuthentication,
  readTenantDomain,
} from '../auth/authStorage';

const apiUrl =
  import.meta.env.VITE_API_URL?.trim() ||
  'http://127.0.0.1:8000/api';

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
      readTenantDomain();

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

      clearAllAuthentication();

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
