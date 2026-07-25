import type {
  LaboratoryOrderPage,
  LaboratoryOrderResource,
} from '../../features/laboratory-orders/types/laboratoryOrder.types';

export const orderUuid = 'b80b4131-9952-4a91-b69b-066dfa856e09';
export const pendingItemUuid = '193a73b1-c554-43a6-a596-50eef7053281';
export const activeItemUuid = '3d3fa30e-a6cb-4474-b41d-0189573786df';
export const resultUuid = '58ae8fb9-8cc1-4455-a1fd-76582dcb3361';
export const reportUuid = '81a3ca82-ef68-4cd8-b576-0dcb3aa2d714';

export const orderResource: LaboratoryOrderResource = {
  uuid: orderUuid,
  order_number: 'LAB-20260724-0001',
  patient: {
    id: 4,
    medical_record_number: 'MRN-ADA-004',
    first_name: 'Ada',
    last_name: 'Okafor',
    full_name: 'Ada Okafor',
  },
  ordered_by: {
    id: 2,
    name: 'Admin User',
    email: 'admin@example.test',
  },
  referring_clinician: 'Dr Amina Yusuf',
  priority: 'urgent',
  status: 'in_progress',
  clinical_notes: 'Review persistent fever.',
  total_amount: '15000.00',
  ordered_at: '2026-07-24T08:00:00.000Z',
  sample_collected_at: '2026-07-24T08:30:00.000Z',
  completed_at: null,
  cancelled_at: null,
  items: [
    {
      uuid: pendingItemUuid,
      lab_test_id: 11,
      test_name: 'Full Blood Count',
      category: 'Haematology',
      price: '7500.00',
      turnaround_time_hours: 4,
      parameters: [{ name: 'Haemoglobin' }],
      status: 'pending',
      processing_started_at: null,
      completed_at: null,
      test_result: null,
      created_at: null,
      updated_at: null,
    },
    {
      uuid: activeItemUuid,
      lab_test_id: 12,
      test_name: 'Malaria Parasite',
      category: 'Parasitology',
      price: '7500.00',
      turnaround_time_hours: 2,
      parameters: [{ name: 'Parasite density' }],
      status: 'completed',
      processing_started_at: '2026-07-24T09:00:00.000Z',
      completed_at: '2026-07-24T10:00:00.000Z',
      test_result: null,
      created_at: null,
      updated_at: null,
    },
  ],
  specimens: [
    {
      uuid: 'cb9c98ca-70e5-4e3c-83e9-fc0a51355566',
      accession_number: 'SP-20260724-A1B2C3D4',
      specimen_type: 'Whole blood',
      container_type: 'EDTA tube',
      collection_site: null,
      volume: '5',
      volume_unit: 'mL',
      status: 'received',
      collected_at: '2026-07-24T08:30:00.000Z',
      received_at: '2026-07-24T08:45:00.000Z',
      rejected_at: null,
      rejection_reason: null,
      notes: null,
    },
  ],
  created_at: '2026-07-24T08:00:00.000Z',
  updated_at: '2026-07-24T10:00:00.000Z',
};

export const completedOrder: LaboratoryOrderResource = {
  ...orderResource,
  status: 'completed',
  completed_at: '2026-07-24T11:00:00.000Z',
};

export const orderPage: LaboratoryOrderPage = {
  data: [orderResource],
  meta: {
    current_page: 1,
    last_page: 1,
    per_page: 15,
    total: 1,
    from: 1,
    to: 1,
  },
};

export const orderResultPage = {
  data: [
    {
      uuid: resultUuid,
      status: 'approved',
      notes: null,
      order_item: {
        uuid: activeItemUuid,
      },
    },
  ],
};
