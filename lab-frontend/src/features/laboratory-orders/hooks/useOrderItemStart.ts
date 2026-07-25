import { useState } from 'react';
import { startLaboratoryOrderItem } from '../api/laboratoryOrderApi';
import { orderErrorMessage } from '../utils/orderDisplay';

export const useOrderItemStart = (
  onStarted: () => void
) => {
  const [startingUuid, setStartingUuid] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);

  const start = async (uuid: string) => {
    setStartingUuid(uuid);
    setError(null);
    try {
      await startLaboratoryOrderItem(uuid);
      onStarted();
    } catch (requestError: unknown) {
      setError(orderErrorMessage(
        requestError,
        'Failed to start laboratory test.'
      ));
    } finally {
      setStartingUuid(null);
    }
  };

  return {
    startingUuid,
    error,
    clearError: () => setError(null),
    start,
  };
};
