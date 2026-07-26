import {
  useEffect,
  useState,
} from 'react';

import {
  fetchReportForOrder,
  generateReportForOrder,
} from '../api/laboratoryOrderApi';

import type {
  LaboratoryReportResource,
} from '../../laboratory-reports/types/laboratoryReport.types';

import {
  orderErrorMessage,
} from '../utils/orderDisplay';

export type ReportAvailability =
  | 'hidden'
  | 'checking'
  | 'missing'
  | 'issued'
  | 'error';

type Action = 'view' | 'generate' | null;

const isMissingReport = (
  error: unknown
): boolean => (
  (
    error as {
      response?: {
        status?: unknown;
      };
    } | null
  )?.response?.status === 404
);

export const useOrderReportActions = (
  uuid: string | undefined,
  enabled: boolean,
  onOpen: (
    report: LaboratoryReportResource
  ) => void
) => {
  const [availability, setAvailability] =
    useState<ReportAvailability>('hidden');

  const [issuedReport, setIssuedReport] =
    useState<LaboratoryReportResource | null>(
      null
    );

  const [action, setAction] =
    useState<Action>(null);

  const [error, setError] =
    useState<string | null>(null);

  useEffect(() => {
    if (!enabled || !uuid) {
      setAvailability('hidden');
      setIssuedReport(null);
      setError(null);
      return;
    }

    let active = true;

    setAvailability('checking');
    setIssuedReport(null);
    setError(null);

    void fetchReportForOrder(uuid)
      .then((report) => {
        if (!active) {
          return;
        }

        setIssuedReport(report);
        setAvailability('issued');
      })
      .catch((requestError: unknown) => {
        if (!active) {
          return;
        }

        if (isMissingReport(requestError)) {
          setAvailability('missing');
          return;
        }

        setAvailability('error');

        setError(
          orderErrorMessage(
            requestError,
            'Failed to determine whether ' +
              'a report has been issued.'
          )
        );
      });

    return () => {
      active = false;
    };
  }, [
    enabled,
    uuid,
  ]);

  const run = async (
    nextAction: Exclude<Action, null>
  ): Promise<void> => {
    if (!uuid) {
      return;
    }

    setAction(nextAction);
    setError(null);

    try {
      const report =
        nextAction === 'view'
          ? await fetchReportForOrder(uuid)
          : await generateReportForOrder(uuid);

      setIssuedReport(report);
      setAvailability('issued');
      onOpen(report);
    } catch (requestError: unknown) {
      setError(
        orderErrorMessage(
          requestError,
          nextAction === 'view'
            ? 'No issued report could be ' +
                'opened for this order.'
            : 'Failed to generate the ' +
                'laboratory report.'
        )
      );
    } finally {
      setAction(null);
    }
  };

  const view = async (): Promise<void> => {
    if (issuedReport) {
      onOpen(issuedReport);
      return;
    }

    await run('view');
  };

  return {
    availability,
    action,
    error,
    clearError: () => setError(null),
    view,
    generate: () => run('generate'),
  };
};