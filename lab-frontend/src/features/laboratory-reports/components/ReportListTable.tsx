import {
  Alert,
  CircularProgress,
  Paper,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  Typography,
} from '@mui/material';

import type {
  LaboratoryReportResource,
} from '../types/laboratoryReport.types';

import {
  formatReportDate,
} from '../utils/reportDisplay';

interface Props {
  reports: LaboratoryReportResource[];
  loading: boolean;
  error: string | null;
  onOpen: (uuid: string) => void;
}

const ReportListTable = ({
  reports,
  loading,
  error,
  onOpen,
}: Props) => {
  if (loading) {
    return (
      <Paper
        variant="outlined"
        sx={{
          py: 8,
          display: 'grid',
          placeItems: 'center',
        }}
      >
        <CircularProgress />
      </Paper>
    );
  }

  if (error) {
    return (
      <Alert severity="error">
        {error}
      </Alert>
    );
  }

  if (reports.length === 0) {
    return (
      <Alert severity="info">
        No laboratory reports match
        the current search.
      </Alert>
    );
  }

  return (
    <TableContainer
      component={Paper}
      variant="outlined"
    >
      <Table>
        <TableHead>
          <TableRow>
            <TableCell>
              Report number
            </TableCell>
            <TableCell>
              Patient
            </TableCell>
            <TableCell>
              Medical record number
            </TableCell>
            <TableCell>
              Order
            </TableCell>
            <TableCell>
              Generated
            </TableCell>
          </TableRow>
        </TableHead>

        <TableBody>
          {reports.map((report) => (
            <TableRow
              key={report.uuid}
              hover
              tabIndex={0}
              role="link"
              onClick={() => {
                onOpen(report.uuid);
              }}
              onKeyDown={(event) => {
                if (
                  event.key === 'Enter' ||
                  event.key === ' '
                ) {
                  onOpen(report.uuid);
                }
              }}
              sx={{
                cursor: 'pointer',
              }}
            >
              <TableCell>
                <Typography
                  variant="body2"
                  sx={{
                    fontWeight: 720,
                  }}
                >
                  {report.report_number}
                </Typography>
              </TableCell>

              <TableCell>
                {report.patient
                  ?.full_name ??
                  'Not recorded'}
              </TableCell>

              <TableCell>
                {report.patient
                  ?.medical_record_number ??
                  'Not recorded'}
              </TableCell>

              <TableCell>
                {report.order
                  ?.order_number ??
                  'Not recorded'}
              </TableCell>

              <TableCell>
                {formatReportDate(
                  report.generated_at
                )}
              </TableCell>
            </TableRow>
          ))}
        </TableBody>
      </Table>
    </TableContainer>
  );
};

export default ReportListTable;
