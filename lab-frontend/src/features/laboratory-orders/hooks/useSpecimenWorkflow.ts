import { useState } from 'react';
import {
  collectOrderSpecimens,
  replaceSpecimen,
  updateSpecimenStatus,
} from '../api/laboratoryOrderApi';
import type {
  CollectSpecimensPayload,
  ReplaceSpecimenPayload,
} from '../types/laboratoryOrder.types';
import { orderErrorMessage } from '../utils/orderDisplay';

type WorkflowAction =
  | { kind: 'collect'; uuid: string }
  | { kind: 'receive'; uuid: string }
  | { kind: 'reject'; uuid: string }
  | { kind: 'replace'; uuid: string }
  | null;

export const useSpecimenWorkflow = (
  onChanged: () => void
) => {
  const [action, setAction] = useState<WorkflowAction>(null);
  const [error, setError] = useState<string | null>(null);

  const run = async (
    nextAction: Exclude<WorkflowAction, null>,
    request: () => Promise<unknown>,
    fallback: string
  ): Promise<boolean> => {
    setAction(nextAction);
    setError(null);
    try {
      await request();
      onChanged();
      return true;
    } catch (requestError: unknown) {
      setError(orderErrorMessage(requestError, fallback));
      return false;
    } finally {
      setAction(null);
    }
  };

  return {
    action,
    error,
    clearError: () => setError(null),
    collect: (
      orderUuid: string,
      payload: CollectSpecimensPayload
    ) => run(
      { kind: 'collect', uuid: orderUuid },
      () => collectOrderSpecimens(orderUuid, payload),
      'Failed to collect specimens.'
    ),
    receive: (uuid: string) => run(
      { kind: 'receive', uuid },
      () => updateSpecimenStatus(uuid, { status: 'received' }),
      'Failed to receive the specimen.'
    ),
    reject: (uuid: string, reason: string) => run(
      { kind: 'reject', uuid },
      () => updateSpecimenStatus(uuid, {
        status: 'rejected',
        rejection_reason: reason.trim(),
      }),
      'Failed to reject the specimen.'
    ),
    replace: (
      uuid: string,
      payload: ReplaceSpecimenPayload
    ) => run(
      { kind: 'replace', uuid },
      () => replaceSpecimen(uuid, payload),
      'Failed to collect a replacement specimen.'
    ),
  };
};
