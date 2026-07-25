import { useState } from 'react';
import {
  fetchReportForOrder,
  generateReportForOrder,
} from '../api/laboratoryOrderApi';
import type { LaboratoryReportResource } from '../../laboratory-reports/types/laboratoryReport.types';
import { orderErrorMessage } from '../utils/orderDisplay';

type Action = 'view' | 'generate' | null;

export const useOrderReportActions = (
  onOpen: (report: LaboratoryReportResource) => void
) => {
  const [action, setAction] = useState<Action>(null);
  const [error, setError] = useState<string | null>(null);

  const run = async (
    nextAction: Exclude<Action, null>,
    uuid: string
  ) => {
    setAction(nextAction);
    setError(null);
    try {
      const report = nextAction === 'view'
        ? await fetchReportForOrder(uuid)
        : await generateReportForOrder(uuid);
      onOpen(report);
    } catch (requestError: unknown) {
      setError(orderErrorMessage(
        requestError,
        nextAction === 'view'
          ? 'No issued report could be opened for this order.'
          : 'Failed to generate the laboratory report.'
      ));
    } finally {
      setAction(null);
    }
  };

  return {
    action,
    error,
    clearError: () => setError(null),
    view: (uuid: string) => run('view', uuid),
    generate: (uuid: string) => run('generate', uuid),
  };
};
