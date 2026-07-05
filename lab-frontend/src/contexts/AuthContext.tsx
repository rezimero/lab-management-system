import React, { createContext, useContext, useState, useEffect, ReactNode } from 'react';
import api from '../api/client';
import { User, Tenant, LoginCredentials, RegisterData, AuthResponse } from '../types';

interface AuthContextType {
  user: User | null;
  tenant: Tenant | null;
  isLoading: boolean;
  isSuperadmin: boolean;
  isImpersonating: boolean;
  login: (credentials: LoginCredentials) => Promise<AuthResponse>;
  register: (data: RegisterData) => Promise<void>;
  logout: () => Promise<void>;
  isAuthenticated: boolean;
  refreshUser: () => Promise<void>;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export const AuthProvider: React.FC<{ children: ReactNode }> = ({ children }) => {
  const [user, setUser] = useState<User | null>(null);
  const [tenant, setTenant] = useState<Tenant | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [isSuperadmin, setIsSuperadmin] = useState<boolean>(() => {
    return localStorage.getItem('is_superadmin') === 'true';
  });
  const [isImpersonating, setIsImpersonating] = useState<boolean>(() => {
    return localStorage.getItem('is_impersonating') === 'true';
  });

  useEffect(() => {
    const token = localStorage.getItem('token');
    const storedUser = localStorage.getItem('user');
    const storedTenant = localStorage.getItem('tenant');
    const storedIsSuperadmin = localStorage.getItem('is_superadmin');
    const storedIsImpersonating = localStorage.getItem('is_impersonating');

    if (token && storedUser) {
      try {
        setUser(JSON.parse(storedUser));
        
        if (storedIsSuperadmin === 'true') {
          setIsSuperadmin(true);
          setTenant(null);
          // Ensure tenant_domain is removed for superadmin
          localStorage.removeItem('tenant_domain');
        } else if (storedTenant) {
          const tenantData = JSON.parse(storedTenant);
          setTenant(tenantData);
          // CRITICAL: Ensure tenant_domain is set from tenant data
          if (tenantData.domain) {
            localStorage.setItem('tenant_domain', tenantData.domain);
            console.log('✅ Restored tenant_domain from tenant:', tenantData.domain);
          }
          setIsSuperadmin(false);
        }

        if (storedIsImpersonating === 'true') {
          setIsImpersonating(true);
        }
      } catch (e) {
        console.error('Error restoring auth state:', e);
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        localStorage.removeItem('tenant');
        localStorage.removeItem('tenant_domain');
        localStorage.removeItem('is_superadmin');
        localStorage.removeItem('is_impersonating');
      }
    }
    setIsLoading(false);
  }, []);

  const login = async (credentials: LoginCredentials): Promise<AuthResponse> => {
    setIsLoading(true);
    try {
      console.log('🔐 Login attempt:', credentials.email);
      
      const response = await api.post<AuthResponse>('/login', credentials);
      const { user, token, tenant, is_superadmin } = response.data;
      
      console.log('🔐 Login response:', { user: user.email, is_superadmin, tenant: tenant?.domain });
      
      // Clear old data
      localStorage.removeItem('tenant_domain');
      localStorage.removeItem('tenant');
      
      // Save token and user
      localStorage.setItem('token', token);
      localStorage.setItem('user', JSON.stringify(user));
      localStorage.setItem('is_superadmin', String(is_superadmin));
      
      if (is_superadmin) {
        // Superadmin - no tenant
        localStorage.removeItem('tenant');
        localStorage.removeItem('tenant_domain');
        setTenant(null);
        setIsSuperadmin(true);
        localStorage.setItem('role', 'superadmin');
        console.log('✅ Superadmin login - no tenant');
      } else {
        // Tenant user - save tenant and tenant_domain
        if (tenant) {
          localStorage.setItem('tenant', JSON.stringify(tenant));
          // CRITICAL: Save tenant domain separately for API headers
          localStorage.setItem('tenant_domain', tenant.domain);
          setTenant(tenant);
          console.log('✅ Tenant login - saved tenant_domain:', tenant.domain);
        } else {
          setTenant(null);
        }
        setIsSuperadmin(false);
        localStorage.setItem('role', user.role);
      }
      
      setUser(user);
      localStorage.removeItem('is_impersonating');
      setIsImpersonating(false);
      
      return response.data;
    } catch (error) {
      console.error('❌ Login error:', error);
      throw error;
    } finally {
      setIsLoading(false);
    }
  };

  const register = async (data: RegisterData) => {
    setIsLoading(true);
    try {
      const response = await api.post<AuthResponse>('/register', data);
      const { user, token, tenant } = response.data;
      
      localStorage.setItem('token', token);
      localStorage.setItem('user', JSON.stringify(user));
      localStorage.setItem('tenant', JSON.stringify(tenant));
      localStorage.setItem('is_superadmin', 'false');
      localStorage.setItem('role', user.role);
      
      // CRITICAL: Save tenant domain for API headers
      if (tenant?.domain) {
        localStorage.setItem('tenant_domain', tenant.domain);
        console.log('✅ Registration - saved tenant_domain:', tenant.domain);
      }
      
      setUser(user);
      setTenant(tenant || null);
      setIsSuperadmin(false);
      setIsImpersonating(false);
    } finally {
      setIsLoading(false);
    }
  };

  const logout = async () => {
    try {
      await api.post('/logout');
    } catch (e) {
      // Ignore errors on logout
    }
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    localStorage.removeItem('tenant');
    localStorage.removeItem('tenant_domain');
    localStorage.removeItem('is_superadmin');
    localStorage.removeItem('role');
    localStorage.removeItem('is_impersonating');
    setUser(null);
    setTenant(null);
    setIsSuperadmin(false);
    setIsImpersonating(false);
  };

  const refreshUser = async () => {
    try {
      const response = await api.get('/user');
      setUser(response.data.user);
      return response.data;
    } catch (error) {
      console.error('Error refreshing user:', error);
    }
  };

  return (
    <AuthContext.Provider
      value={{
        user,
        tenant,
        isLoading,
        isSuperadmin,
        isImpersonating,
        login,
        register,
        logout,
        isAuthenticated: !!user,
        refreshUser,
      }}
    >
      {children}
    </AuthContext.Provider>
  );
};

export const useAuth = (): AuthContextType => {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within AuthProvider');
  }
  return context;
};