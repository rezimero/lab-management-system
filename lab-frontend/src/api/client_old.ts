import axios from 'axios';

const api = axios.create({
  baseURL: process.env.REACT_APP_API_URL || 'http://localhost:8000/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

// Request interceptor to automatically attach authentication
// and tenant context to every API request.
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('token');
    const isSuperadmin = localStorage.getItem('is_superadmin') === 'true';
    const tenantJson = localStorage.getItem('tenant');

    // Attach Bearer token
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }

    // Attach tenant domain - ONLY for non-superadmin users
    if (!isSuperadmin && tenantJson) {
      try {
        const tenant = JSON.parse(tenantJson);

        if (tenant?.domain) {
          config.headers['X-Tenant-Domain'] = tenant.domain;
        }
      } catch (error) {
        console.error('Failed to parse tenant from localStorage:', error);
      }
    }

    // Log request details (useful for debugging)
    if (process.env.NODE_ENV === 'development') {
      console.log('🚀 API Request');
      console.log('Method:', config.method?.toUpperCase());
      console.log('URL:', config.url);
      console.log('Headers:', config.headers);
      console.log('Body:', config.data);
    }

    return config;
  },
  (error) => Promise.reject(error)
);

// Response interceptor
api.interceptors.response.use(
  (response) => {
    if (process.env.NODE_ENV === 'development') {
      console.log('✅ API Response:', response.status, response.data);
    }
    return response;
  },
  (error) => {
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