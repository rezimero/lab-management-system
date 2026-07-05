export interface User {
  id: number;
  name: string;
  email: string;
  role: string;
  is_active: boolean;
  created_at: string;
  updated_at: string;
  roles?: { name: string }[];
  tenant_name?: string;  // Added for superadmin view
  tenant_domain?: string; // Added for superadmin view
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
}

export interface AuthResponse {
  message: string;
  user: User;
  token: string;
  tenant?: Tenant;          // Made optional - superadmin has no tenant
  is_superadmin: boolean;   // Added for superadmin detection
  guard?: 'landlord' | 'tenant'; // Added to identify which guard was used
}

export interface LoginCredentials {
  email: string;
  password: string;
  domain?: string;          // Made optional - superadmin doesn't need domain
}

export interface RegisterData {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
  tenant_name: string;
  tenant_domain: string;
}

// ============================================================
// LANDLORD / SUPERADMIN SPECIFIC TYPES
// ============================================================

export interface LandlordUser {
  id: number;
  name: string;
  email: string;
  role: 'superadmin';
  is_active: boolean;
  created_at: string;
  updated_at: string;
}

export interface LandlordAuthResponse {
  message: string;
  user: LandlordUser;
  token: string;
  is_superadmin: true;
  guard: 'landlord';
}

export interface DashboardStats {
  total_tenants: number;
  total_users: number;
  total_patients: number;
  total_lab_tests: number;
  total_test_results: number;
}

export interface TenantWithStats extends Tenant {
  stats: {
    users: number;
    patients: number;
    lab_tests: number;
    test_results: number;
  };
}