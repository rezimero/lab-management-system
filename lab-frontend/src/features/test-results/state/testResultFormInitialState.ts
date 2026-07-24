import type {
  LoadedResultForm,
} from '../types/testResultForm.types';

import {
  createEmptyRow,
} from '../utils/resultFormMapper';

export const createInitialResultForm =
  (): LoadedResultForm => ({
    rows: [createEmptyRow()],
    notes: '',
    status: 'draft',
    context: {
      orderNumber: 'Not recorded',
      patientName: 'Not recorded',
      medicalRecordNumber: '',
      testName: 'Not recorded',
      category: 'Uncategorised',
    },
    blockingMessage: null,
    existingResult: null,
    rejectionReason: null,
  });
