import axios, {
  AxiosError,
  AxiosInstance,
  InternalAxiosRequestConfig,
} from 'axios';

import {
  sanitizeServerError,
} from '../../../api/serverErrorSanitizer';

import {
  clearPatientPortalSession,
  readPatientPortalSession,
} from '../auth/patientPortalStorage';

const apiUrl =
  import.meta.env.VITE_API_URL?.trim()
  || 'http://127.0.0.1:8000/api';

const patientPortalClient: AxiosInstance =
  axios.create({
    baseURL: apiUrl,
    headers: {
      Accept: 'application/json',
    },
    withCredentials: false,
  });

patientPortalClient.interceptors.request.use(
  (
    config: InternalAxiosRequestConfig
  ): InternalAxiosRequestConfig => {
    const session =
      readPatientPortalSession();

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

patientPortalClient.interceptors.response.use(
  (response) => response,
  (error: AxiosError) => {
    const safeError =
      sanitizeServerError(error);

    if (safeError.response?.status === 401) {
      const requestUrl =
        safeError.config?.url ?? '';

      const isLoginRequest =
        requestUrl.endsWith(
          '/portal/patient/login'
        );

      if (!isLoginRequest) {
        clearPatientPortalSession();

        if (
          typeof window !== 'undefined'
          && window.location.pathname
            .startsWith('/patient-portal')
          && window.location.pathname
            !== '/patient-portal/login'
        ) {
          window.location.assign(
            '/patient-portal/login'
          );
        }
      }
    }

    return Promise.reject(safeError);
  }
);

export default patientPortalClient;
