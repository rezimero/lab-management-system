export type LaboratoryOrderStatus =
  | 'pending'
  | 'sample_collected'
  | 'in_progress'
  | 'completed'
  | 'cancelled';

export type LaboratoryOrderPriority =
  | 'routine'
  | 'urgent'
  | 'stat';

export type LaboratorySpecimenStatus =
  | 'collected'
  | 'received'
  | 'rejected';

export interface OrderUserSummary {
  id: number;
  name: string;
  email: string;
}

export interface OrderPatientSummary {
  id: number;
  medical_record_number: string;
  first_name: string;
  last_name: string;
  full_name: string;
}

export interface OrderReferringClinicianSummary {
  uuid: string;
  name: string;
  email: string | null;
  phone: string | null;
  organisation: string | null;
  registration_number: string | null;
  specialty: string | null;
  is_active: boolean;
}

export interface OrderTestResultSummary {
  uuid: string;
  status: string;
  notes?: string | null;
  order_item?: { uuid: string } | null;
}

export interface OrderTestResultPage {
  data: OrderTestResultSummary[];
}

export interface LaboratoryOrderItemResource {
  uuid: string;
  lab_test_id: number | null;
  test_name: string;
  category: string | null;
  price: string | number;
  turnaround_time_hours: number | null;
  parameters: unknown[] | null;
  status: string;
  processing_started_by?: OrderUserSummary | null;
  processing_started_at: string | null;
  completed_at: string | null;
  test_result?: OrderTestResultSummary | null;
  created_at: string | null;
  updated_at: string | null;
}

export interface SpecimenChainSummary {
  uuid: string;
  accession_number: string;
  specimen_type: string;
  status: LaboratorySpecimenStatus;
}

export interface LaboratoryOrderSpecimen {
  uuid: string;
  accession_number: string;
  specimen_type: string;
  container_type: string | null;
  collection_site: string | null;
  volume: string | number | null;
  volume_unit: string | null;
  status: LaboratorySpecimenStatus;
  collected_at: string | null;
  received_at: string | null;
  rejected_at: string | null;
  rejection_reason: string | null;
  notes: string | null;
  order_item_uuids?: string[];
  collected_by?: OrderUserSummary | null;
  replaces_specimen?: SpecimenChainSummary | null;
  replacement?: SpecimenChainSummary | null;
}

export interface SpecimenCollectionInput {
  specimen_type: string;
  container_type?: string;
  collection_site?: string;
  volume?: number;
  volume_unit?: string;
  notes?: string;
  order_item_uuids: string[];
}

export interface CollectSpecimensPayload {
  specimens: SpecimenCollectionInput[];
}

export interface SpecimenStatusPayload {
  status: 'received' | 'rejected';
  rejection_reason?: string;
}

export interface ReplaceSpecimenPayload {
  specimen_type?: string;
  container_type?: string;
  collection_site?: string;
  volume?: number;
  volume_unit?: string;
  notes?: string;
}

export interface LaboratoryOrderResource {
  uuid: string;
  order_number: string;
  patient?: OrderPatientSummary;
  ordered_by?: OrderUserSummary | null;
  referring_clinician: string | null;
  referring_clinician_record?: OrderReferringClinicianSummary | null;
  priority: LaboratoryOrderPriority;
  status: LaboratoryOrderStatus;
  clinical_notes: string | null;
  total_amount: string | number;
  ordered_at: string | null;
  sample_collected_at: string | null;
  completed_at: string | null;
  cancelled_at: string | null;
  items: LaboratoryOrderItemResource[];
  specimens: LaboratoryOrderSpecimen[];
  created_at: string | null;
  updated_at: string | null;
}

export interface LaboratoryOrderPageMeta {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from?: number | null;
  to?: number | null;
}

export interface LaboratoryOrderPage {
  data: LaboratoryOrderResource[];
  meta: LaboratoryOrderPageMeta;
}
