import type {
  TestResultResource,
  TestResultStatus,
} from '../../../types';

export const RESULT_FLAGS = [
  'normal',
  'low',
  'high',
  'critical',
  'abnormal',
] as const;

export type ResultFlag =
  | (typeof RESULT_FLAGS)[number]
  | '';

export interface ResultFormRow {
  name: string;
  value: string;
  unit: string;
  reference_range: string;
  flag: ResultFlag;
  notes: string;
}

export interface ResultFormPayload {
  results_data: Array<{
    name: string;
    value: string | null;
    unit: string | null;
    reference_range: string | null;
    flag: Exclude<ResultFlag, ''> | null;
    notes: string | null;
  }>;
  notes: string | null;
}

export interface LaboratoryOrderPatient {
  id: number;
  medical_record_number: string;
  first_name: string;
  last_name: string;
  full_name: string;
}

export interface LaboratoryOrderItemDetail {
  uuid: string;
  test_name: string;
  category: string | null;
  status: string;
  parameters: unknown[];
  test_result?: TestResultResource | null;
}

export interface LaboratoryOrderDetail {
  uuid: string;
  order_number: string;
  priority: string;
  status: string;
  patient?: LaboratoryOrderPatient | null;
  items: LaboratoryOrderItemDetail[];
}

export interface ResultFormContext {
  orderNumber: string;
  patientName: string;
  medicalRecordNumber: string;
  testName: string;
  category: string;
}

export interface LoadedResultForm {
  rows: ResultFormRow[];
  notes: string;
  status: TestResultStatus;
  context: ResultFormContext;
  blockingMessage: string | null;
  existingResult: TestResultResource | null;
  rejectionReason: string | null;
}
