import {
  Alert,
  Box,
  CircularProgress,
} from '@mui/material';

import {
  useNavigate,
  useParams,
} from 'react-router-dom';

import Layout from
  '../components/Layout/Layout';

import LaboratoryReportPaper from
  '../features/laboratory-reports/components/LaboratoryReportPaper';

import ReportPrintStyles from
  '../features/laboratory-reports/components/ReportPrintStyles';

import ReportViewHeader from
  '../features/laboratory-reports/components/ReportViewHeader';

import {
  useDownloadLaboratoryReportPdf,
} from '../features/laboratory-reports/hooks/useDownloadLaboratoryReportPdf';

import {
  useLaboratoryReport,
} from '../features/laboratory-reports/hooks/useLaboratoryReport';

const LaboratoryReportView = () => {
  const {
    id: uuid,
  } = useParams<{
    id: string;
  }>();

  const navigate = useNavigate();

  const resource =
    useLaboratoryReport(uuid);

  const pdfDownload =
    useDownloadLaboratoryReportPdf(
      uuid,
      resource.report?.report_number
    );

  if (resource.loading) {
    return (
      <Layout title="Laboratory report">
        <Box
          sx={{
            py: 8,
            display: 'grid',
            placeItems: 'center',
          }}
        >
          <CircularProgress />
        </Box>
      </Layout>
    );
  }

  if (
    resource.error ||
    !resource.report
  ) {
    return (
      <Layout title="Laboratory report">
        <Alert severity="error">
          {resource.error ??
            'Laboratory report not found'}
        </Alert>
      </Layout>
    );
  }

  return (
    <Layout title="Laboratory report">
      <ReportPrintStyles />

      <ReportViewHeader
        downloadingPdf={
          pdfDownload.downloading
        }
        onBack={() => {
          navigate(
            '/laboratory-reports'
          );
        }}
        onDownloadPdf={() => {
          void pdfDownload.download();
        }}
        onPrint={() => {
          window.print();
        }}
      />

      {pdfDownload.error && (
        <Alert
          className="no-print"
          severity="error"
          sx={{ mb: 2 }}
        >
          {pdfDownload.error}
        </Alert>
      )}

      <Box
        className="report-print-root"
        data-testid="report-print-root"
      >
        <LaboratoryReportPaper
          report={resource.report}
        />
      </Box>
    </Layout>
  );
};

export default LaboratoryReportView;