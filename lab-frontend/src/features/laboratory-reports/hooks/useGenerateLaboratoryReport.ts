import {
  useState,
} from 'react';

import {
  generateLaboratoryReport,
} from '../api/laboratoryReportApi';

import type {
  LaboratoryReportResource,
} from '../types/laboratoryReport.types';

import {
  reportErrorMessage,
} from '../utils/reportDisplay';

export const useGenerateLaboratoryReport =
  (
    onGenerated: (
      report: LaboratoryReportResource
    ) => void
  ) => {
    const [
      open,
      setOpen,
    ] = useState(false);

    const [
      orderUuid,
      setOrderUuid,
    ] = useState('');

    const [
      submitting,
      setSubmitting,
    ] = useState(false);

    const [
      error,
      setError,
    ] = useState<string | null>(null);

    const generate = async () => {
      const uuid = orderUuid.trim();

      if (!uuid) {
        setError(
          'A laboratory order UUID is required.'
        );
        return;
      }

      setSubmitting(true);
      setError(null);

      try {
        const report =
          await generateLaboratoryReport(
            uuid
          );

        setOpen(false);
        setOrderUuid('');
        onGenerated(report);
      } catch (requestError: unknown) {
        setError(
          reportErrorMessage(
            requestError,
            'Failed to generate laboratory report.'
          )
        );
      } finally {
        setSubmitting(false);
      }
    };

    return {
      open,
      orderUuid,
      submitting,
      error,
      setOrderUuid,
      openDialog: () => {
        setError(null);
        setOpen(true);
      },
      closeDialog: () => {
        if (!submitting) {
          setOpen(false);
        }
      },
      generate,
    };
  };
