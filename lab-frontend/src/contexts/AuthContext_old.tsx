import React, { createContext, useContext, useState, useEffect, ReactNode } from 'react';
import api from '../api/client';
import { User, Tenant, LoginCredentials, RegisterData, AuthResponse } from '../types';

interface AuthContextType {
  user: User | null;
  tenant: Tenant | null;
  isLoading: boolean;
  isSuperadmin: boolean;
  login: (credentials: LoginCredentials) => Promise<AuthResponse>;
  register: (data: RegisterData) => Promise<void>;
  logout: () => Promise<void>;
  isAuthenticated: boolean;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export const AuthProvider: React.FC<{ children: ReactNode }> = ({ children }) => {
  const [user, setUser] = useState<User | null>(null);
  const [tenant, setTenant] = useState<Tenant | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [isSuperadmin, setIsSuperadmin] = useState<boolean>(() => {
    return localStorage.getItem('is_superadmin') === 'true';
  });

  useEffect(() => {
    const token = localStorage.getItem('token');
    const storedUser = localStorage.getItem('user');
    const storedTenant = localStorage.getItem('tenant');
    const storedIsSuperadmin = localStorage.getItem('is_superadmin');

    if (token && storedUser) {
      try {
        setUser(JSON.parse(storedUser));
        
        // Check if superadmin
        if (storedIsSuperadmin === 'true') {
          setIsSuperadmin(true);
          setTenant(null);
        } else if (storedTenant) {
          setTenant(JSON.parse(storedTenant));
          setIsSuperadmin(false);
        }
      } catch (e) {
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        localStorage.removeItem('tenant');
        localStorage.removeItem('is_superadmin');
      }
    }
    setIsLoading(false);
  }, []);

  const login = async (credentials: LoginCredentials): Promise<AuthResponse> => {
    setIsLoading(true);
    try {
      const response = await api.post<AuthResponse>('/login', credentials);
      const { user, token, tenant, is_superadmin } = response.data;
      
      // Store token
      localStorage.setItem('token', token);
      localStorage.setItem('user', JSON.stringify(user));
      localStorage.setItem('is_superadmin', String(is_superadmin));
      
      if (is_superadmin) {
        // Superadmin - no tenant
        localStorage.removeItem('tenant');
        setTenant(null);
        setIsSuperadmin(true);
        localStorage.setItem('role', 'superadmin');
      } else {
        // Tenant user
        if (tenant) {
          localStorage.setItem('tenant', JSON.stringify(tenant));
          setTenant(tenant);
        } else {
          setTenant(null);
        }
        setIsSuperadmin(false);
        localStorage.setItem('role', user.role);
      }
      
      setUser(user);
      
      return response.data;
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
      
      setUser(user);
      setTenant(tenant || null);
      setIsSuperadmin(false);
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
    localStorage.removeItem('is_superadmin');
    localStorage.removeItem('role');
    setUser(null);
    setTenant(null);
    setIsSuperadmin(false);
  };

  return (
    <AuthContext.Provider
      value={{
        user,
        tenant,
        isLoading,
        isSuperadmin,
        login,
        register,
        logout,
        isAuthenticated: !!user,
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