export type AnalyzerTransportMode =
  | 'gateway'
  | 'direct_http';

export type AnalyzerImportStatus =
  | 'received'
  | 'needs_review'
  | 'ready'
  | 'imported'
  | 'rejected'
  | 'failed'
  | 'duplicate';

export interface AnalyzerBranchSummary {
  uuid: string;
  code: string;
  name: string;
  type: string;
}

export interface AnalyzerInstrument {
  uuid: string;
  code: string;
  name: string;
  manufacturer?: string | null;
  model?: string | null;
  serial_number?: string | null;
  adapter_key: string;
  transport_mode: AnalyzerTransportMode;
  machine_token_hint?: string | null;
  token_rotated_at?: string | null;
  is_active: boolean;
  last_seen_at?: string | null;
  last_success_at?: string | null;
  last_error_at?: string | null;
  last_error_code?: string | null;
  notes?: string | null;
  branch?: AnalyzerBranchSummary;
  test_mappings_count?: number;
  messages_count?: number;
  imports_count?: number;
  created_at?: string | null;
  updated_at?: string | null;
}

export interface AnalyzerLabTest {
  id: number;
  uuid: string;
  name: string;
  category: string;
  parameters: Array<{
    name?: string;
    unit?: string | null;
    reference_range?: string | null;
  }>;
  is_active: boolean;
}

export interface AnalyzerParameterMapping {
  uuid: string;
  instrument_parameter_code: string;
  instrument_parameter_name?: string | null;
  target_parameter_name: string;
  expected_unit?: string | null;
  is_required: boolean;
  is_active: boolean;
  created_at?: string | null;
  updated_at?: string | null;
}

export interface AnalyzerTestMapping {
  uuid: string;
  instrument_test_code: string;
  instrument_test_name?: string | null;
  is_active: boolean;
  lab_test?: AnalyzerLabTest;
  parameter_mappings_count?: number;
  parameter_mappings?: AnalyzerParameterMapping[];
  created_at?: string | null;
  updated_at?: string | null;
}

export interface AnalyzerInstrumentPayload {
  branch_uuid: string;
  code: string;
  name: string;
  manufacturer?: string;
  model?: string;
  serial_number?: string;
  adapter_key?: 'canonical_json_v1';
  transport_mode?: AnalyzerTransportMode;
  notes?: string;
}

export interface AnalyzerTestMappingPayload {
  lab_test_uuid: string;
  instrument_test_code: string;
  instrument_test_name?: string;
  is_active?: boolean;
}

export interface AnalyzerParameterMappingPayload {
  instrument_parameter_code: string;
  instrument_parameter_name?: string;
  target_parameter_name: string;
  expected_unit?: string;
  is_required?: boolean;
  is_active?: boolean;
}

export interface AnalyzerCredentialResult {
  instrument: AnalyzerInstrument;
  machine_token: string;
  machine_token_notice?: string;
}

export interface AnalyzerImportEvent {
  uuid?: string;
  event_type: string;
  from_status?: string | null;
  to_status?: string | null;
  actor_type?: string | null;
  actor_snapshot?: Record<string, unknown> | null;
  payload?: Record<string, unknown> | null;
  occurred_at?: string | null;
}

export interface AnalyzerResultImport {
  uuid: string;
  source_result_key: string;
  source_sample_identifier: string;
  instrument_test_code: string;
  status: AnalyzerImportStatus;
  normalized_results?: Array<Record<string, unknown>> | null;
  mapping_snapshot?: Record<string, unknown> | null;
  validation_issues?: Array<Record<string, unknown>> | null;
  attempts: number;
  last_processed_at?: string | null;
  accepted_at?: string | null;
  rejected_at?: string | null;
  review_note?: string | null;
  instrument?: {
    uuid: string;
    code: string;
    name: string;
  } | null;
  message?: {
    uuid: string;
    external_message_id?: string | null;
    status: string;
    received_at?: string | null;
  } | null;
  specimen?: {
    uuid: string;
    accession_number: string;
    status: string;
  } | null;
  order_item?: {
    uuid: string;
    test_name: string;
    status: string;
    order_uuid?: string | null;
    order_number?: string | null;
  } | null;
  test_result?: {
    uuid: string;
    status: string;
  } | null;
  events?: AnalyzerImportEvent[];
  created_at?: string | null;
  updated_at?: string | null;
}

export interface AnalyzerPage<T> {
  data: T[];
  meta?: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
  statistics?: Record<string, number>;
}

export interface AnalyzerEnvelope<T> {
  data: T;
  message?: string;
  machine_token?: string;
  machine_token_notice?: string;
}
