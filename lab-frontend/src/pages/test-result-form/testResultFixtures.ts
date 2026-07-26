export const orderUuid =
  '7c18bbcb-ab40-45b8-8118-932313dc18ff';

export const orderItemUuid =
  '19c2924f-6364-457a-a0e4-b9f9ca8e67f2';

export const resultUuid =
  'f3e64296-9fc3-4bb5-9702-a6c72e7f10c8';

export const orderResource = {
  uuid: orderUuid,
  order_number: 'LAB-2026-0042',
  priority: 'urgent',
  status: 'in_progress',
  patient: {
    id: 3,
    medical_record_number: 'MRN-001',
    first_name: 'Ada',
    last_name: 'Lovelace',
    full_name: 'Ada Lovelace',
  },
  items: [
    {
      uuid: orderItemUuid,
      test_name:
        'Complete Blood Count',
      category: 'Haematology',
      status: 'in_progress',
      parameters: [
        {
          name: 'Haemoglobin',
          unit: 'g/dL',
          reference_range:
            '12.0-16.0',
        },
      ],
      test_result: null,
    },
  ],
};

export const resultResource = {
  uuid: resultUuid,
  status: 'draft' as const,
  results_data: [
    {
      name: 'Haemoglobin',
      value: '13.6',
      unit: 'g/dL',
      reference_range:
        '12.0-16.0',
      flag: 'normal',
      notes: null,
    },
  ],
  notes: 'Initial draft note',
  rejection_reason: null,
  order_item: {
    uuid: orderItemUuid,
    test_name:
      'Complete Blood Count',
    category: 'Haematology',
    status: 'in_progress',
    parameters: [],
    order: {
      uuid: orderUuid,
      order_number:
        'LAB-2026-0042',
      priority: 'urgent',
      status: 'in_progress',
      patient: {
        id: 3,
        medical_record_number:
          'MRN-001',
        first_name: 'Ada',
        last_name: 'Lovelace',
        full_name: 'Ada Lovelace',
      },
    },
  },
  entered_by: null,
  submitted_by: null,
  reviewed_by: null,
  submitted_at: null,
  reviewed_at: null,
  created_at:
    '2026-07-21T09:00:00.000Z',
  updated_at:
    '2026-07-21T09:00:00.000Z',
};
