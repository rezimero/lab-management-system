import axios, {
  AxiosError,
  AxiosInstance,
  InternalAxiosRequestConfig,
} from 'axios';

import {
  sanitizeServerError,
} from '../../../api/serverErrorSanitizer';

import {
  clearClinicianPortalSession,
  readClinicianPortalSession,
} from '../auth/clinicianPortalStorage';

const apiUrl =
  import.meta.env.VITE_API_URL?.trim()
  || 'http://127.0.0.1:8000/api';

const clinicianPortalClient: AxiosInstance =
  axios.create({
    baseURL: apiUrl,
    headers: {
      Accept: 'application/json',
    },
    withCredentials: false,
  });

clinicianPortalClient.interceptors.request.use(
  (
    config: InternalAxiosRequestConfig
  ): InternalAxiosRequestConfig => {
    const session =
      readClinicianPortalSession();

    if (session?.token) {
      config.headers.Authorization =
        `Bearer ${session.token}`;
    } else {
      delete config.headers.Authorization;
    }

    if (session?.domain) {
      config.headers['X-Tenant-Domain'] =
        session.domain;
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

clinicianPortalClient.interceptors.response.use(
  (response) => response,
  (error: AxiosError) => {
    const safeError =
      sanitizeServerError(error);

    if (safeError.response?.status === 401) {
      const requestUrl =
        safeError.config?.url ?? '';

      const isLoginRequest =
        requestUrl.endsWith(
          '/portal/clinician/login'
        );

      if (!isLoginRequest) {
        clearClinicianPortalSession();

        if (
          typeof window !== 'undefined'
          && window.location.pathname
            .startsWith('/clinician-portal')
          && window.location.pathname
            !== '/clinician-portal/login'
        ) {
          window.location.assign(
            '/clinician-portal/login'
          );
        }
      }
    }

    return Promise.reject(safeError);
  }
);

export default clinicianPortalClient;
