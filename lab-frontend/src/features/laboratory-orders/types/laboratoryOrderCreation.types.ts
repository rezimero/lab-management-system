import type {
  LaboratoryOrderPriority,
  LaboratoryOrderResource,
} from './laboratoryOrder.types';

export interface OrderPatientOption {
  id: number;
  first_name: string;
  last_name: string;
  full_name?: string;
  medical_record_number: string;
  is_active?: boolean;
}

export interface OrderLabTestOption {
  id: number;
  name: string;
  category: string;
  price: string | number;
  turnaround_time_hours?: number | null;
  is_active?: boolean;
}

export interface CreateLaboratoryOrderPayload {
  patient_id: number;
  referring_clinician?: string;
  priority: LaboratoryOrderPriority;
  clinical_notes?: string;
  items: Array<{ lab_test_id: number }>;
}

export interface OrderCreationState {
  patient: OrderPatientOption | null;
  tests: OrderLabTestOption[];
  priority: LaboratoryOrderPriority;
  referringClinician: string;
  clinicalNotes: string;
}

export interface OrderCreationResult {
  order: LaboratoryOrderResource;
  message?: string;
}
