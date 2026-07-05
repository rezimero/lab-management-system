import axios, { AxiosInstance, InternalAxiosRequestConfig, AxiosError } from 'axios';

const api: AxiosInstance = axios.create({
  baseURL: process.env.REACT_APP_API_URL || 'http://localhost:8000/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  withCredentials: true,
});

// ============================================================
// REQUEST INTERCEPTOR - Adds token AND tenant domain
// ============================================================
api.interceptors.request.use(
  (config: InternalAxiosRequestConfig): InternalAxiosRequestConfig => {
    console.log('========================================');
    console.log('🔵 INTERCEPTOR - Adding headers');
    
    // Get data from localStorage
    const token = localStorage.getItem('token');
    const isSuperadmin = localStorage.getItem('is_superadmin') === 'true';
    const tenantDomain = localStorage.getItem('tenant_domain');
    
    console.log('📌 Token:', token ? '✅ Present' : '❌ Missing');
    console.log('📌 Is Superadmin:', isSuperadmin);
    console.log('📌 Tenant Domain:', tenantDomain || '❌ Missing');
    
    // ALWAYS add Authorization header if token exists
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
      console.log('✅ Authorization header added');
    }
    
    // IMPORTANT: ALWAYS add X-Tenant-Domain for non-superadmin users
    if (tenantDomain && !isSuperadmin) {
      config.headers['X-Tenant-Domain'] = tenantDomain;
      console.log('✅ X-Tenant-Domain header added:', tenantDomain);
    } else if (!tenantDomain && !isSuperadmin) {
      console.warn('⚠️ No tenant domain found for non-superadmin user!');
      // Try to get tenant from user object
      const userStr = localStorage.getItem('user');
      if (userStr) {
        try {
          const user = JSON.parse(userStr);
          const tenantStr = localStorage.getItem('tenant');
          if (tenantStr) {
            const tenant = JSON.parse(tenantStr);
            if (tenant.domain) {
              localStorage.setItem('tenant_domain', tenant.domain);
              config.headers['X-Tenant-Domain'] = tenant.domain;
              console.log('✅ Recovered tenant domain from tenant object:', tenant.domain);
            }
          }
        } catch (e) {
          console.error('Error parsing user/tenant data:', e);
        }
      }
    }
    
    console.log('📤 Final Headers:', config.headers);
    console.log('📤 URL:', config.url);
    console.log('========================================');
    
    return config;
  },
  (error: AxiosError): Promise<AxiosError> => {
    console.error('❌ Interceptor Error:', error);
    return Promise.reject(error);
  }
);

// ============================================================
// RESPONSE INTERCEPTOR - Handles errors
// ============================================================
api.interceptors.response.use(
  (response) => {
    console.log('✅ API Response:', response.status, response.config.url);
    return response;
  },
  (error: AxiosError) => {
    console.error('❌ API Error:', error.response?.status, error.response?.data);
    console.error('❌ Error config:', error.config);
    
    if (error.response?.status === 401) {
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