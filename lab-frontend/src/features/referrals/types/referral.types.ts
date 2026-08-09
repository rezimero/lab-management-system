export interface ReferralSummary {
  total: number;
  draft: number;
  dispatched: number;
  acknowledged: number;
  awaiting_result?: number;
  result_received: number;
  completed: number;
  rejected: number;
  cancelled: number;
  overdue: number;
}

export interface ReferralLaboratory {
  uuid: string;
  name: string;
  code?: string;
  city?: string;
  state?: string;
  contact_person?: string;
  email?: string;
  phone?: string;
  is_active: boolean;
  test_mappings_count?: number;
  referrals_count?: number;
  test_mappings?: ReferralTestMapping[];
}

export interface ReferralTestMapping {
  uuid: string;
  laboratory_uuid?: string;
  test_uuid?: string;
  test_name?: string;
  external_test_code?: string;
  external_test_name?: string;
  cost?: string | number;
  currency?: string;
  turnaround_hours?: number | null;
  specimen_requirements?: ReferralSpecimenRequirements | null;
  is_active?: boolean;
  lab_test?: {
    uuid: string;
    id?: number | string;
    name?: string;
    category?: string;
    is_active?: boolean;
  } | null;
  referral_laboratory?: {
    uuid?: string;
    code?: string;
    name?: string;
  } | null;
}

export interface ReferralPatientSummary {
  first_name?: string;
  last_name?: string;
  medical_record_number?: string;
  full_name?: string;
}

export interface ReferralOrderSummary {
  uuid?: string;
  order_number?: string;
  patient?: ReferralPatientSummary;
}

export interface ReferralOrderItemSummary {
  uuid?: string;
  test_name?: string;
  order?: ReferralOrderSummary;
}

export interface Referral {
  uuid: string;
  referral_number: string;
  status: string;
  created_at?: string | null;
  turnaround_due_at?: string | null;
  is_overdue?: boolean;
  is_terminal?: boolean;
  rejection_reason?: string | null;
  cancellation_reason?: string | null;
  timestamps?: {
    created_at?: string | null;
    updated_at?: string | null;
    dispatched_at?: string | null;
    acknowledged_at?: string | null;
    result_received_at?: string | null;
    completed_at?: string | null;
    rejected_at?: string | null;
    cancelled_at?: string | null;
  };
  quoted_cost?: string | number | null;
  final_cost?: string | number | null;
  currency?: string | null;
  external_reference?: string | null;
  dispatch_notes?: string | null;
  order_item?: ReferralOrderItemSummary;
  laboratory?: {
    uuid?: string;
    code?: string;
    name?: string;
    accreditation_number?: string | null;
    contact_name?: string | null;
    email?: string | null;
    phone?: string | null;
    address?: {
      line_1?: string | null;
      line_2?: string | null;
      city?: string | null;
      state?: string | null;
      country?: string | null;
    } | null;
  } | null;
  referral_laboratory?: ReferralLaboratory;
  laboratory_snapshot?: {
    uuid?: string;
    code?: string;
    name?: string;
    accreditation_number?: string | null;
    contact_name?: string | null;
    email?: string | null;
    phone?: string | null;
    address_line_1?: string | null;
    address_line_2?: string | null;
    city?: string | null;
    state?: string | null;
    country?: string | null;
  } | null;
  test_snapshot?: {
    uuid?: string;
    lab_test_uuid?: string | null;
    local_test_name?: string | null;
    local_test_category?: string | null;
    external_test_code?: string | null;
    external_test_name?: string | null;
    cost?: string | number | null;
    currency?: string | null;
    turnaround_hours?: number | null;
    specimen_requirements?: ReferralSpecimenRequirements | null;
  } | null;
  test?: {
    uuid?: string;
    lab_test_uuid?: string | null;
    local_test_name?: string | null;
    local_test_category?: string | null;
    external_test_code?: string | null;
    external_test_name?: string | null;
    cost?: string | number | null;
    currency?: string | null;
    turnaround_hours?: number | null;
    specimen_requirements?: ReferralSpecimenRequirements | null;
  } | null;
  latest_external_result?: {
    uuid: string;
    version: number;
    external_report_number?: string | null;
    reported_at?: string | null;
    received_at?: string | null;
    source_payload_hash?: string | null;
  } | null;
  standard_result?: {
    uuid: string;
    status: string;
  } | null;
  external_results?: ReferralExternalResult[];
  timeline?: ReferralEvent[];
  events?: ReferralEvent[];
  created_by?: {
    id?: number;
    name?: string;
    email?: string;
  } | null;
  creator?: {
    id?: number;
    name?: string;
  } | null;
  created_by_name?: string;
}

export interface ReferralEvent {
  uuid?: string;
  event_type?: string;
  status?: string;
  description?: string;
  from_status?: string | null;
  to_status?: string | null;
  payload?: Record<string, unknown> | null;
  actor?: {
    id?: number;
    name?: string;
    email?: string;
  } | null;
  occurred_at?: string | null;
  created_at?: string;
}

export interface ReferralPageMeta {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

export interface ReferralPage {
  data: Referral[];
  meta: ReferralPageMeta;
}

export interface ReferralSpecimenOption {
  uuid: string;
  accession_number: string;
  specimen_type: string;
  container_type?: string | null;
  status: string;
}

export interface ReferralOrderItemOption {
  uuid: string;
  order_uuid: string;
  order_number: string;
  order_status: string;
  patient_name: string;
  medical_record_number?: string;
  test_name: string;
  status?: string;
  lab_test_id?: number | null;
  specimens: ReferralSpecimenOption[];
}

export interface ReferralCreateMappingOption {
  uuid: string;
  laboratory_uuid: string;
  laboratory_name: string;
  laboratory_code?: string;
  external_test_code?: string;
  external_test_name?: string;
  cost?: string | number;
  currency?: string;
  turnaround_hours?: number | null;
  specimen_requirements?: ReferralSpecimenRequirements | null;
}

export interface CreateReferralPayload {
  order_item_uuid: string;
  referral_laboratory_test_uuid: string;
  specimen_uuids: string[];
  quoted_cost?: string | number;
  external_reference?: string;
  dispatch_notes?: string;
}

export interface ReferralLaboratoryPayload {
  name: string;
  code?: string;
  contact_person?: string;
  email?: string;
  phone?: string;
}

export interface PaginatedResponse<T> {
  data: T[];
  current_page: number;
  last_page: number;
  total: number;
}


export interface ReferralLabTestOption {
  uuid: string;
  id?: number | string;
  name: string;
  category?: string;
  is_active?: boolean;
}

export interface ReferralSpecimenRequirements {
  specimen_type?: string | null;
  container?: string | null;
  minimum_volume?: string | null;
  instructions?: string | null;
}

export interface ReferralTestMappingPayload {
  lab_test_uuid: string;
  external_test_code: string;
  external_test_name: string;
  cost: string | number;
  currency: string;
  turnaround_hours?: number | null;
  specimen_requirements?: ReferralSpecimenRequirements | null;
  is_active?: boolean;
}


export type ReferralResultFlag =
  | "normal"
  | "low"
  | "high"
  | "critical"
  | "abnormal";

export interface ReferralExternalResultRow {
  name: string;
  value?: string | null;
  unit?: string | null;
  reference_range?: string | null;
  flag?: ReferralResultFlag | null;
  notes?: string | null;
}

export interface ReferralExternalResult {
  uuid: string;
  version: number;
  supersedes_uuid?: string | null;
  external_report_number?: string | null;
  results_data?: ReferralExternalResultRow[];
  notes?: string | null;
  reported_at?: string | null;
  received_at?: string | null;
  receiver?: {
    id?: number;
    name?: string;
    email?: string;
  } | null;
  receiver_snapshot?: Record<string, unknown> | null;
  source_payload_hash?: string | null;
  test_result?: {
    uuid: string;
    status: string;
  } | null;
  created_at?: string | null;
}

export interface CaptureReferralResultPayload {
  external_report_number?: string;
  results_data: ReferralExternalResultRow[];
  notes?: string;
  reported_at?: string;
  final_cost?: string | number;
}


export interface ReferralReasonPayload {
  reason: string;
}
