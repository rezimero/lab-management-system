import type {
  TestResultStatus,
} from '../../../types';

import {
  fetchLaboratoryOrder,
  fetchTestResult,
} from '../api/testResultFormApi';

import type {
  LoadedResultForm,
  ResultFormContext,
} from '../types/testResultForm.types';

import {
  normalizeRows,
  resultContextFromResource,
} from '../utils/resultFormMapper';

export const isEditableResultStatus = (
  status: TestResultStatus
): boolean => (
  status === 'draft' ||
  status === 'rejected'
);

export const loadCreateResultForm = async (
  orderUuid: string,
  orderItemUuid: string
): Promise<LoadedResultForm> => {
  const order = await fetchLaboratoryOrder(
    orderUuid
  );

  const item = (
    Array.isArray(order.items)
      ? order.items
      : []
  ).find(
    (candidate) =>
      candidate.uuid === orderItemUuid
  );

  if (!item) {
    throw new Error(
      'The selected laboratory order item could not be found.'
    );
  }

  const existingResult =
    item.test_result?.uuid
      ? item.test_result
      : null;

  let blockingMessage: string | null = null;

  if (existingResult) {
    blockingMessage =
      'This laboratory order item already has a test result.';
  } else if (order.status !== 'in_progress') {
    blockingMessage =
      'The laboratory order must be in progress before results can be entered.';
  } else if (item.status !== 'in_progress') {
    blockingMessage =
      'Start processing this laboratory order item before entering a result.';
  }

  const context: ResultFormContext = {
    orderNumber: order.order_number,
    patientName:
      order.patient?.full_name ?? 'Not recorded',
    medicalRecordNumber:
      order.patient?.medical_record_number ?? '',
    testName: item.test_name,
    category: item.category ?? 'Uncategorised',
  };

  return {
    rows: normalizeRows(item.parameters),
    notes: '',
    status: 'draft',
    context,
    blockingMessage,
    existingResult,
    rejectionReason: null,
  };
};

export const loadEditResultForm = async (
  resultUuid: string
): Promise<LoadedResultForm> => {
  const result = await fetchTestResult(
    resultUuid
  );

  return {
    rows: normalizeRows(result.results_data),
    notes: result.notes ?? '',
    status: result.status,
    context: resultContextFromResource(result),
    blockingMessage:
      isEditableResultStatus(result.status)
        ? null
        : `A ${result.status} test result cannot be edited.`,
    existingResult: null,
    rejectionReason:
      result.status === 'rejected'
        ? result.rejection_reason
        : null,
  };
};
