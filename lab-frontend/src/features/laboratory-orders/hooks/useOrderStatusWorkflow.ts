import { useState } from 'react';
import { updateLaboratoryOrderStatus } from '../api/laboratoryOrderApi';
import type { LaboratoryOrderStatus } from '../types/laboratoryOrder.types';
import { orderErrorMessage } from '../utils/orderDisplay';

export type OrderTransitionStatus = Extract<
  LaboratoryOrderStatus,
  'in_progress' | 'completed' | 'cancelled'
>;

export const useOrderStatusWorkflow = (
  onChanged: () => void
) => {
  const [changingTo, setChangingTo] = useState<OrderTransitionStatus | null>(null);
  const [error, setError] = useState<string | null>(null);

  const transition = async (
    uuid: string,
    status: OrderTransitionStatus
  ): Promise<boolean> => {
    setChangingTo(status);
    setError(null);
    try {
      await updateLaboratoryOrderStatus(uuid, status);
      onChanged();
      return true;
    } catch (requestError: unknown) {
      setError(orderErrorMessage(
        requestError,
        'Failed to update the laboratory order.'
      ));
      return false;
    } finally {
      setChangingTo(null);
    }
  };

  return {
    changingTo,
    error,
    clearError: () => setError(null),
    transition,
  };
};
