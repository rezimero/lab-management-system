import {
  useEffect,
  useState,
} from 'react';

import {
  fetchLaboratoryReport,
} from '../api/laboratoryReportApi';

import type {
  LaboratoryReportResource,
} from '../types/laboratoryReport.types';

import {
  reportErrorMessage,
} from '../utils/reportDisplay';

export const useLaboratoryReport = (
  uuid?: string
) => {
  const [
    report,
    setReport,
  ] = useState<
    LaboratoryReportResource | null
  >(null);

  const [
    loading,
    setLoading,
  ] = useState(true);

  const [
    error,
    setError,
  ] = useState<string | null>(null);

  useEffect(() => {
    let active = true;

    const load = async () => {
      if (!uuid) {
        if (active) {
          setError(
            'A laboratory report UUID was not provided.'
          );
          setLoading(false);
        }

        return;
      }

      setLoading(true);
      setError(null);

      try {
        const loaded =
          await fetchLaboratoryReport(
            uuid
          );

        if (active) {
          setReport(loaded);
        }
      } catch (requestError: unknown) {
        if (active) {
          setReport(null);
          setError(
            reportErrorMessage(
              requestError,
              'Failed to load laboratory report.'
            )
          );
        }
      } finally {
        if (active) {
          setLoading(false);
        }
      }
    };

    void load();

    return () => {
      active = false;
    };
  }, [uuid]);

  return {
    report,
    loading,
    error,
  };
};
