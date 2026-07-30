import {
  useRef,
  useState,
} from 'react';

import {
  downloadLaboratoryReportPdf,
} from '../api/laboratoryReportApi';

import {
  downloadBlob,
  reportPdfFilename,
} from '../utils/downloadBlob';

import {
  reportErrorMessage,
} from '../utils/reportDisplay';

export const useDownloadLaboratoryReportPdf =
  (
    uuid?: string,
    reportNumber?: string
  ) => {
    const [
      downloading,
      setDownloading,
    ] = useState(false);

    const [
      error,
      setError,
    ] = useState<string | null>(null);

    const active = useRef(false);

    const download = async () => {
      if (active.current) {
        return;
      }

      if (!uuid) {
        setError(
          'A laboratory report UUID was not provided.'
        );

        return;
      }

      active.current = true;
      setDownloading(true);
      setError(null);

      try {
        const response =
          await downloadLaboratoryReportPdf(
            uuid
          );

        downloadBlob(
          response.blob,
          reportPdfFilename(
            response.contentDisposition,
            reportNumber
          )
        );
      } catch (requestError: unknown) {
        setError(
          reportErrorMessage(
            requestError,
            'Failed to download laboratory report PDF.'
          )
        );
      } finally {
        active.current = false;
        setDownloading(false);
      }
    };

    return {
      downloading,
      error,
      download,
    };
  };