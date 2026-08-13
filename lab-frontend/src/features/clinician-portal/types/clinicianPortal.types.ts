export type ClinicianPortalOrderStatus =
  | 'pending'
  | 'sample_collected'
  | 'in_progress'
  | 'completed'
  | 'cancelled';

export type ClinicianPortalOrderPriority =
  | 'routine'
  | 'urgent'
  | 'stat';

export type ClinicianPortalReportLifecycle =
  | 'issued'
  | 'amended';

export interface ClinicianPortalAccount {
  uuid: string;
  email: string;
  is_active: boolean;
  activated_at: string | null;
  last_login_at: string | null;
}

export interface ClinicianPortalClinician {
  uuid: string | null;
  name: string | null;
  email: string | null;
  phone: string | null;
  organisation: string | null;
  registration_number: string | null;
  specialty: string | null;
}

export interface ClinicianPortalContext {
  portal: 'clinician';
  account: ClinicianPortalAccount;
  clinician: ClinicianPortalClinician;
}

export interface ClinicianPortalLoginResponse
  extends ClinicianPortalContext {
  message: string;
  token: string;
  expires_at: string | null;
}

export interface ClinicianPortalPatient {
  medical_record_number: string | null;
  first_name: string | null;
  last_name: string | null;
  full_name: string | null;
  date_of_birth: string | null;
  gender: string | null;
}

export interface ClinicianPortalOrderItem {
  uuid: string;
  test_name: string;
  category: string | null;
  status: string;
  turnaround_time_hours: number | null;
}

export interface ClinicianPortalReportSummary {
  uuid: string;
  report_number: string;
  version: number;
  lifecycle_status: ClinicianPortalReportLifecycle;
  generated_at: string | null;
}

export interface ClinicianPortalOrder {
  uuid: string;
  order_number: string;
  priority: ClinicianPortalOrderPriority;
  status: ClinicianPortalOrderStatus;
  referring_clinician: string | null;
  ordered_at: string | null;
  sample_collected_at: string | null;
  completed_at: string | null;
  cancelled_at: string | null;
  patient: ClinicianPortalPatient | null;
  items: ClinicianPortalOrderItem[];
  report?: ClinicianPortalReportSummary | null;
}

export interface ClinicianPortalLaboratorySummary {
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

export interface ClinicianPortalReportPatient {
  medical_record_number: string | null;
  first_name: string | null;
  last_name: string | null;
  full_name: string | null;
  date_of_birth: string | null;
  gender: string | null;
}

export interface ClinicianPortalReportOrder {
  uuid: string | null;
  order_number: string | null;
  priority: string | null;
  status: string | null;
  referring_clinician: string | null;
  ordered_at: string | null;
  sample_collected_at: string | null;
  completed_at: string | null;
}

export interface ClinicianPortalResultEntry {
  name?: string | null;
  value?: string | number | boolean | null;
  unit?: string | null;
  reference_range?: string | null;
  flag?: string | null;
  notes?: string | null;
  [key: string]: unknown;
}

export interface ClinicianPortalReportTest {
  order_item_uuid: string | null;
  test_name: string | null;
  category: string | null;
  parameters: unknown[];
  effective_version: number;
  results_data: ClinicianPortalResultEntry[];
  notes: string | null;
}

export interface ClinicianPortalReportAmendment {
  reason: string | null;
  amended_at: string | null;
}

export interface ClinicianPortalReport {
  uuid: string;
  report_number: string;
  version: number;
  lifecycle_status: ClinicianPortalReportLifecycle;
  is_current: true;
  generated_at: string | null;
  amended_at: string | null;
  laboratory: ClinicianPortalLaboratorySummary;
  patient: ClinicianPortalReportPatient;
  order: ClinicianPortalReportOrder;
  results: ClinicianPortalReportTest[];
  amendment: ClinicianPortalReportAmendment | null;
}

export interface ClinicianPortalPageMeta {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from?: number | null;
  to?: number | null;
}

export interface ClinicianPortalPage<T> {
  data: T[];
  meta: ClinicianPortalPageMeta;
  links?: {
    first?: string | null;
    last?: string | null;
    prev?: string | null;
    next?: string | null;
  };
}
