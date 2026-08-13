export type PatientPortalOrderStatus =
  | 'pending'
  | 'sample_collected'
  | 'in_progress'
  | 'completed'
  | 'cancelled';

export type PatientPortalOrderPriority =
  | 'routine'
  | 'urgent'
  | 'stat';

export type PatientPortalReportLifecycle =
  | 'issued'
  | 'amended';

export interface PatientPortalAccount {
  uuid: string;
  email: string;
  is_active: boolean;
  activated_at: string | null;
  last_login_at: string | null;
}

export interface PatientPortalPatient {
  medical_record_number: string | null;
  first_name: string | null;
  last_name: string | null;
  full_name: string | null;
}

export interface PatientPortalContext {
  portal: 'patient';
  account: PatientPortalAccount;
  patient: PatientPortalPatient;
}

export interface PatientPortalLoginResponse
  extends PatientPortalContext {
  message: string;
  token: string;
  expires_at: string | null;
}

export interface PatientPortalOrderItem {
  uuid: string;
  test_name: string;
  category: string | null;
  status: string;
  turnaround_time_hours: number | null;
}

export interface PatientPortalReportSummary {
  uuid: string;
  report_number: string;
  version: number;
  lifecycle_status: PatientPortalReportLifecycle;
  generated_at: string | null;
}

export interface PatientPortalOrder {
  uuid: string;
  order_number: string;
  priority: PatientPortalOrderPriority;
  status: PatientPortalOrderStatus;
  referring_clinician: string | null;
  ordered_at: string | null;
  sample_collected_at: string | null;
  completed_at: string | null;
  cancelled_at: string | null;
  items: PatientPortalOrderItem[];
  report?: PatientPortalReportSummary | null;
}

export interface PatientPortalLaboratorySummary {
  name: string | null;
  tagline: string | null;
  email: string | null;
  phone: string | null;
  website: string | null;
  address: string | null;
  city: string | null;
  state: string | null;
  country: string | null;
  license_number: string | null;
  registration_number: string | null;
  accreditation_details: string | null;
  report_footer: string | null;
  director_name: string | null;
  director_qualification: string | null;
  director_registration_number: string | null;
  signatory_designation: string | null;
}

export interface PatientPortalReportPatient {
  medical_record_number: string | null;
  first_name: string | null;
  last_name: string | null;
  full_name: string | null;
  date_of_birth: string | null;
  gender: string | null;
}

export interface PatientPortalReportOrder {
  uuid: string | null;
  order_number: string | null;
  priority: string | null;
  status: string | null;
  referring_clinician: string | null;
  ordered_at: string | null;
  sample_collected_at: string | null;
  completed_at: string | null;
}

export interface PatientPortalResultEntry {
  name?: string | null;
  value?: string | number | boolean | null;
  unit?: string | null;
  reference_range?: string | null;
  flag?: string | null;
  notes?: string | null;
  [key: string]: unknown;
}

export interface PatientPortalReportTest {
  order_item_uuid: string | null;
  test_name: string | null;
  category: string | null;
  parameters: unknown[];
  effective_version: number;
  results_data: PatientPortalResultEntry[];
  notes: string | null;
}

export interface PatientPortalReportAmendment {
  reason: string | null;
  amended_at: string | null;
}

export interface PatientPortalReport {
  uuid: string;
  report_number: string;
  version: number;
  lifecycle_status: PatientPortalReportLifecycle;
  is_current: true;
  generated_at: string | null;
  amended_at: string | null;
  laboratory: PatientPortalLaboratorySummary;
  patient: PatientPortalReportPatient;
  order: PatientPortalReportOrder;
  results: PatientPortalReportTest[];
  amendment: PatientPortalReportAmendment | null;
}

export interface PatientPortalPageMeta {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from?: number | null;
  to?: number | null;
}

export interface PatientPortalPage<T> {
  data: T[];
  meta: PatientPortalPageMeta;
  links?: {
    first?: string | null;
    last?: string | null;
    prev?: string | null;
    next?: string | null;
  };
}
