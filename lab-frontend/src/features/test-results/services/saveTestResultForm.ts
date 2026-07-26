import {
  createDraftResult,
  updateDraftResult,
} from '../api/testResultFormApi';

import type {
  ResultFormRow,
} from '../types/testResultForm.types';

import {
  buildResultPayload,
} from '../utils/resultFormMapper';

interface Options {
  resultUuid?: string;
  orderItemUuid: string | null;
  rows: ResultFormRow[];
  notes: string;
}

export const saveTestResultForm = async ({
  resultUuid,
  orderItemUuid,
  rows,
  notes,
}: Options): Promise<string | null> => {
  const payload = buildResultPayload(
    rows,
    notes
  );

  if (resultUuid) {
    const result = await updateDraftResult(
      resultUuid,
      payload
    );

    return result.uuid;
  }

  if (!orderItemUuid) {
    return null;
  }

  const result = await createDraftResult(
    orderItemUuid,
    payload
  );

  return result.uuid;
};
