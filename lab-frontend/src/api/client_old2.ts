import axios, { AxiosInstance, InternalAxiosRequestConfig, AxiosError } from 'axios';

const api: AxiosInstance = axios.create({
  baseURL: process.env.REACT_APP_API_URL || 'http://localhost:8000/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

api.interceptors.request.use(
  (config: InternalAxiosRequestConfig): InternalAxiosRequestConfig => {
    const token = localStorage.getItem('token');
    const isSuperadmin = localStorage.getItem('is_superadmin') === 'true';
    const tenantDomain = localStorage.getItem('tenant_domain');

    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }

    if (tenantDomain && !isSuperadmin) {
      config.headers['X-Tenant-Domain'] = tenantDomain;
    }

    if (process.env.NODE_ENV === 'development') {
      console.log('🚀 API Request:', config.method?.toUpperCase(), config.url);
    }

    return config;
  },
  (error: AxiosError): Promise<AxiosError> => Promise.reject(error)
);

api.interceptors.response.use(
  (response) => response,
  (error: AxiosError) => {
    console.error('❌ API Error:', error.response?.status, error.response?.data);

    if (error.response?.status === 401) {
      localStorage.removeItem('token');
      localStorage.removeItem('user');
      localStorage.removeItem('tenant');
      localStorage.removeItem('is_superadmin');
      localStorage.removeItem('role');
      window.location.href = '/login';
    }

    return Promise.reject(error);
  }
);

export default api;