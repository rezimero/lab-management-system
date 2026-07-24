import type {
  TestResultResource,
  TestResultStatus,
} from '../../types';

export const resultUuid =
  'f3e64296-9fc3-4bb5-9702-a6c72e7f10c8';

export const resultResource:
  TestResultResource = {
    uuid: resultUuid,
    status: 'approved',
    results_data: [
      {
        name: 'Haemoglobin',
        value: '17.2',
        unit: 'g/dL',
        reference_range:
          '12.0-16.0',
        flag: 'high',
        notes:
          'Confirm with repeat sample',
      },
    ],
    notes:
      'Clinical correlation is advised.',
    rejection_reason: null,
    order_item: {
      uuid:
        '19c2924f-6364-457a-a0e4-b9f9ca8e67f2',
      test_name:
        'Complete Blood Count',
      category: 'Haematology',
      status: 'completed',
      parameters: [],
      order: {
        uuid:
          '7c18bbcb-ab40-45b8-8118-932313dc18ff',
        order_number:
          'LAB-2026-0042',
        priority: 'urgent',
        status: 'completed',
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
    entered_by: {
      id: 4,
      name:
        'Laboratory Technician',
      email:
        'technician@example.test',
    },
    submitted_by: {
      id: 4,
      name:
        'Laboratory Technician',
      email:
        'technician@example.test',
    },
    reviewed_by: {
      id: 5,
      name: 'Laboratory Reviewer',
      email:
        'reviewer@example.test',
    },
    submitted_at:
      '2026-07-21T10:00:00.000Z',
    reviewed_at:
      '2026-07-21T10:30:00.000Z',
    created_at:
      '2026-07-21T09:00:00.000Z',
    updated_at:
      '2026-07-21T10:30:00.000Z',
  };

export const resultWithStatus = (
  status: TestResultStatus
): TestResultResource => ({
  ...resultResource,
  status,
  rejection_reason:
    status === 'rejected'
      ? 'Submitted values require correction.'
      : null,
  reviewed_by:
    status === 'approved' ||
    status === 'rejected'
      ? resultResource.reviewed_by
      : null,
  reviewed_at:
    status === 'approved' ||
    status === 'rejected'
      ? resultResource.reviewed_at
      : null,
  submitted_by:
    status === 'draft'
      ? null
      : resultResource.submitted_by,
  submitted_at:
    status === 'draft'
      ? null
      : resultResource.submitted_at,
});
