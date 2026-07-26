import type {
  TestResultResource,
} from '../../../types';

import type {
  ResultFormContext,
  ResultFormPayload,
  ResultFormRow,
} from '../types/testResultForm.types';

const nullableText = (
  value: string
): string | null => {
  const normalized = value.trim();

  return normalized === ''
    ? null
    : normalized;
};

export const buildResultPayload = (
  rows: ResultFormRow[],
  notes: string
): ResultFormPayload => ({
  results_data: rows.map((row) => ({
    name: row.name.trim(),
    value: nullableText(row.value),
    unit: nullableText(row.unit),
    reference_range: nullableText(
      row.reference_range
    ),
    flag: row.flag === ''
      ? null
      : row.flag,
    notes: nullableText(row.notes),
  })),
  notes: nullableText(notes),
});

export const resultContextFromResource = (
  result: TestResultResource
): ResultFormContext => {
  const orderItem = result.order_item;
  const order = orderItem?.order;
  const patient = order?.patient;

  return {
    orderNumber:
      order?.order_number ??
      'Not recorded',

    patientName:
      patient?.full_name ??
      'Not recorded',

    medicalRecordNumber:
      patient?.medical_record_number ??
      '',

    testName:
      orderItem?.test_name ??
      'Not recorded',

    category:
      orderItem?.category ??
      'Uncategorised',
  };
};
