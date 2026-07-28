import type {
  LaboratoryReportResource,
} from '../../features/laboratory-reports/types/laboratoryReport.types';

export const reportUuid =
  '4f57d8db-e9bc-47ce-b06f-220263fd4872';

export const orderUuid =
  'cf9e1301-1d49-44cf-8ee4-e369b31a52e9';

export const reportResource:
  LaboratoryReportResource = {
    uuid: reportUuid,
    report_number:
      'RPT-LAB-2026-0042',
    source_order_uuid:
      orderUuid,
    laboratory: {
      lab_name:
        'Clinical Diagnostics Laboratory',
      tagline:
        'Accurate results, trusted care',
      logo_url:
        '/storage/laboratory-logo.png',
      full_address:
        '12 Medical Avenue, Abuja',
      director_name:
        'Dr Report Director',
      report_footer:
        'This report must be interpreted by a qualified clinician.',
    },
    patient: {
      id: 3,
      medical_record_number:
        'MRN-001',
      first_name: 'Ada',
      last_name: 'Lovelace',
      full_name: 'Ada Lovelace',
      date_of_birth:
        '1990-01-01',
      gender: 'female',
    },
    order: {
      uuid: orderUuid,
      order_number:
        'LAB-2026-0042',
      priority: 'urgent',
      status: 'completed',
      referring_clinician:
        'Dr Grace',
      ordered_at:
        '2026-07-21T08:00:00.000Z',
      completed_at:
        '2026-07-21T10:30:00.000Z',
    },
    results: [
      {
        order_item_uuid:
          'bf5c3133-83d8-4c3f-86cf-0bbd1e16404c',
        test_name:
          'Complete Blood Count',
        category: 'Haematology',
        result_uuid:
          'f3e64296-9fc3-4bb5-9702-a6c72e7f10c8',
        results_data: [
          {
            name: 'Haemoglobin',
            value: '17.2',
            unit: 'g/dL',
            reference_range:
              '12.0-16.0',
            flag: 'high',
          },
        ],
        notes:
          'Clinical correlation advised.',
      },
    ],
    generated_by: {
      id: 5,
      name:
        'Laboratory Reviewer',
      email:
        'reviewer@example.test',
    },
    generated_at:
      '2026-07-21T11:00:00.000Z',
    created_at:
      '2026-07-21T11:00:00.000Z',
  };

export const reportPage = {
  data: [reportResource],
  meta: {
    current_page: 1,
    last_page: 1,
    per_page: 25,
    total: 1,
    from: 1,
    to: 1,
  },
};
