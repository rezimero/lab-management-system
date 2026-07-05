import axios, {
  AxiosInstance,
  InternalAxiosRequestConfig,
  AxiosError,
} from 'axios';

const api: AxiosInstance = axios.create({
  baseURL: process.env.REACT_APP_API_URL || 'http://localhost:8000/api',
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
  // IMPORTANT:
  // We use Bearer tokens, NOT Sanctum cookie authentication.
  // Therefore withCredentials must be false (or omitted).
  withCredentials: false,
});

// ============================================================
// REQUEST INTERCEPTOR - Adds Bearer Token and Tenant Domain
// ============================================================
api.interceptors.request.use(
  (config: InternalAxiosRequestConfig): InternalAxiosRequestConfig => {
    console.log('========================================');
    console.log('🔵 INTERCEPTOR - Adding headers');

    const token = localStorage.getItem('token');
    const isSuperadmin = localStorage.getItem('is_superadmin') === 'true';
    let tenantDomain = localStorage.getItem('tenant_domain');

    console.log('📌 Token:', token ? '✅ Present' : '❌ Missing');
    console.log('📌 Is Superadmin:', isSuperadmin);
    console.log('📌 Tenant Domain:', tenantDomain || '❌ Missing');

    // Always send Bearer token
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
      console.log('✅ Authorization header added');
    }

    // Tenant users require tenant domain
    if (!isSuperadmin) {
      if (!tenantDomain) {
        const tenantStr = localStorage.getItem('tenant');

        if (tenantStr) {
          try {
            const tenant = JSON.parse(tenantStr);

            if (tenant?.domain) {
              tenantDomain = tenant.domain;
              localStorage.setItem('tenant_domain', tenant.domain);

              console.log(
                '✅ Restored tenant_domain from tenant:',
                tenant.domain
              );
            }
          } catch (err) {
            console.error('Failed to parse tenant object:', err);
          }
        }
      }

      if (tenantDomain) {
        config.headers['X-Tenant-Domain'] = tenantDomain;
        console.log(
          '✅ X-Tenant-Domain header added:',
          tenantDomain
        );
      } else {
        console.warn(
          '⚠️ Tenant user has no tenant domain stored.'
        );
      }
    } else {
      console.log(
        '👑 Superadmin request - skipping X-Tenant-Domain header'
      );
    }

    console.log('📤 Final Headers:', config.headers);
    console.log('📤 URL:', config.url);
    console.log('========================================');

    return config;
  },
  (error: AxiosError) => {
    console.error('❌ Request Interceptor Error:', error);
    return Promise.reject(error);
  }
);

// ============================================================
// RESPONSE INTERCEPTOR
// ============================================================
api.interceptors.response.use(
  (response) => {
    console.log(
      '✅ API Response:',
      response.status,
      response.config.url
    );
    return response;
  },
  (error: AxiosError) => {
    console.error(
      '❌ API Error:',
      error.response?.status,
      error.response?.data
    );

    if (error.response?.status === 401) {
      console.warn('🔒 Unauthorized. Clearing local storage.');

      localStorage.removeItem('token');
      localStorage.removeItem('user');
      localStorage.removeItem('tenant');
      localStorage.removeItem('tenant_domain');
      localStorage.removeItem('is_superadmin');
      localStorage.removeItem('role');

      window.location.href = '/login';
    }

    return Promise.reject(error);
  }
);

export default api;