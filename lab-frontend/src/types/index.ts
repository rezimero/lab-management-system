export interface User {
  id: number;
  name: string;
  email: string;
  role: string;
  is_active: boolean;
  created_at: string;
  updated_at: string;
  roles?: { name: string }[];
  tenant_name?: string;
  tenant_domain?: string;
}

export interface Tenant {
  id: number;
  name: string;
  domain: string;
  database: string;
  is_active: boolean;
  trial_ends_at: string | null;
  created_at: string;
  updated_at: string;
  stats?: {
    users: number;
    patients: number;
    lab_tests: number;
    test_results: number;
  };
}

export interface Patient {
  id: number;
  first_name: string;
  last_name: string;
  date_of_birth: string;
  gender: 'male' | 'female' | 'other';
  email: string | null;
  phone: string;
  address: string | null;
  medical_record_number: string;
  notes: string | null;
  is_active: boolean;
  created_at: string;
  updated_at: string;
  tenant_name?: string;
  tenant_domain?: string;
}

export interface LabTest {
  id: number;
  name: string;
  description: string | null;
  category: string;
  price: string;
  turnaround_time_hours: number;
  parameters: any[];
  is_active: boolean;
  created_at: string;
  updated_at: string;
  tenant_name?: string;
  tenant_domain?: string;
}

export interface TestResult {
  id: number;
  patient_id: number;
  lab_test_id: number;
  user_id: number;
  test_date: string;
  results_data: any[];
  notes: string | null;
  status: 'pending' | 'in_progress' | 'completed' | 'cancelled';
  completed_at: string | null;
  created_at: string;
  updated_at: string;
  patient?: Patient;
  lab_test?: LabTest;
  user?: User;
  tenant_name?: string;
  tenant_domain?: string;
}

export interface AuthResponse {
  message: string;
  user: User;
  token: string;
  tenant?: Tenant;
  is_superadmin: boolean;
  guard?: 'landlord' | 'tenant';
}

export interface LoginCredentials {
  email: string;
  password: string;
  domain?: string;
}

export interface RegisterData {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
  tenant_name: string;
  tenant_domain: string;
}

export interface DashboardStats {
  total_tenants: number;
  total_users: number;
  total_patients: number;
  total_lab_tests: number;
  total_test_results: number;
  tenants: {
    id: number;
    name: string;
    domain: string;
    is_active: boolean;
    stats: {
      users: number;
      patients: number;
      lab_tests: number;
      test_results: number;
    };
  }[];
}

export interface ImpersonationStatus {
  is_impersonating: boolean;
  tenant: {
    id: number;
    name: string;
  } | null;
  user: {
    id: number;
    email: string;
  } | null;
}