export interface ReportUser {
  id: number;
  name: string;
  email: string;
}

export interface ReportLaboratory {
  lab_name?: string | null;
  tagline?: string | null;
  email?: string | null;
  phone?: string | null;
  website?: string | null;
  full_address?: string | null;
  address?: string | null;
  city?: string | null;
  state?: string | null;
  country?: string | null;
  license_number?: string | null;
  registration_number?: string | null;
  report_footer?: string | null;
  director_name?: string | null;
  director_qualification?: string | null;
  director_registration_number?: string | null;
  signatory_designation?: string | null;
  accreditation_details?: string | null;
  logo_url?: string | null;
  director_signature_url?: string | null;
}

export interface ReportPatient {
  id?: number;
  medical_record_number?: string | null;
  first_name?: string | null;
  last_name?: string | null;
  full_name?: string | null;
  date_of_birth?: string | null;
  gender?: string | null;
  email?: string | null;
  phone?: string | null;
  address?: string | null;
}

export interface ReportOrder {
  uuid?: string | null;
  order_number?: string | null;
  priority?: string | null;
  status?: string | null;
  referring_clinician?: string | null;
  clinical_notes?: string | null;
  total_amount?: string | number | null;
  ordered_at?: string | null;
  sample_collected_at?: string | null;
  completed_at?: string | null;
  ordered_by?: ReportUser | null;
}

export interface ReportResultValue {
  name: string;
  value: string | number | null;
  unit?: string | null;
  reference_range?: string | null;
  flag?: string | null;
  notes?: string | null;
}

export interface ReportResultItem {
  order_item_uuid?: string | null;
  test_name?: string | null;
  category?: string | null;
  result_uuid?: string | null;
  results_data?: ReportResultValue[];
  notes?: string | null;
  entered_by?: ReportUser | null;
  submitted_by?: ReportUser | null;
  reviewed_by?: ReportUser | null;
  submitted_at?: string | null;
  reviewed_at?: string | null;
}

export interface LaboratoryReportResource {
  uuid: string;
  report_number: string;
  source_order_uuid?: string | null;
  laboratory: ReportLaboratory;
  patient: ReportPatient;
  order: ReportOrder;
  results: ReportResultItem[];
  generated_by?: ReportUser | null;
  generated_at?: string | null;
  created_at?: string | null;
}

export interface ReportPageMeta {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from?: number | null;
  to?: number | null;
}

export interface LaboratoryReportPage {
  data: LaboratoryReportResource[];
  meta: ReportPageMeta;
}
