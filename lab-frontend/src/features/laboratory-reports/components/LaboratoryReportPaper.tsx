import {
  Paper,
} from '@mui/material';

import type {
  LaboratoryReportResource,
} from '../types/laboratoryReport.types';

import ReportIdentitySection from
  './ReportIdentitySection';

import ReportPatientOrderSection from
  './ReportPatientOrderSection';

import ReportResultsSection from
  './ReportResultsSection';

import ReportSignatureFooter from
  './ReportSignatureFooter';

interface Props {
  report: LaboratoryReportResource;
}

const LaboratoryReportPaper = ({
  report,
}: Props) => (
  <Paper
    className="report-paper"
    sx={{
      p: {
        xs: 2,
        sm: 3,
        lg: 4,
      },
      borderRadius: 3,
      border: '1px solid',
      borderColor: 'divider',
      backgroundColor: '#FFFFFF',
    }}
  >
    <ReportIdentitySection
      report={report}
    />

    <ReportPatientOrderSection
      report={report}
    />

    <ReportResultsSection
      results={
        Array.isArray(report.results)
          ? report.results
          : []
      }
    />

    <ReportSignatureFooter
      report={report}
    />
  </Paper>
);

export default LaboratoryReportPaper;
