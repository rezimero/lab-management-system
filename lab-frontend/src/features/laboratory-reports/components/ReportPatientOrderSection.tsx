import {
  Grid,
  Paper,
  Typography,
} from '@mui/material';

import type {
  LaboratoryReportResource,
} from '../types/laboratoryReport.types';

import {
  formatReportDate,
  formatReportLabel,
} from '../utils/reportDisplay';

const Field = ({
  label,
  value,
}: {
  label: string;
  value: string;
}) => (
  <Typography
    variant="body2"
    sx={{ mb: 1 }}
  >
    <Typography
      component="span"
      color="text.secondary"
      sx={{ mr: 0.75 }}
    >
      {label}:
    </Typography>
    {value}
  </Typography>
);

interface Props {
  report: LaboratoryReportResource;
}

const ReportPatientOrderSection = ({
  report,
}: Props) => (
  <Grid
    className="report-patient-order"
    container
    spacing={2.5}
    sx={{ mb: 3 }}
  >
    <Grid
      size={{
        xs: 12,
        md: 6,
      }}
    >
      <Paper
        className="report-detail-card"
        variant="outlined"
        sx={{
          p: 2.25,
          height: '100%',
        }}
      >
        <Typography
          variant="subtitle1"
          sx={{
            mb: 1.5,
            fontWeight: 750,
          }}
        >
          Patient
        </Typography>

        <Field
          label="Full name"
          value={
            report.patient?.full_name ??
            'Not recorded'
          }
        />
        <Field
          label="MRN"
          value={
            report.patient
              ?.medical_record_number ??
            'Not recorded'
          }
        />
        <Field
          label="Date of birth"
          value={
            report.patient
              ?.date_of_birth ??
            'Not recorded'
          }
        />
        <Field
          label="Gender"
          value={formatReportLabel(
            report.patient?.gender
          )}
        />
      </Paper>
    </Grid>

    <Grid
      size={{
        xs: 12,
        md: 6,
      }}
    >
      <Paper
        className="report-detail-card"
        variant="outlined"
        sx={{
          p: 2.25,
          height: '100%',
        }}
      >
        <Typography
          variant="subtitle1"
          sx={{
            mb: 1.5,
            fontWeight: 750,
          }}
        >
          Laboratory order
        </Typography>

        <Field
          label="Order number"
          value={
            report.order?.order_number ??
            'Not recorded'
          }
        />
        <Field
          label="Referring clinician"
          value={
            report.order
              ?.referring_clinician ??
            'Not recorded'
          }
        />
        <Field
          label="Ordered"
          value={formatReportDate(
            report.order?.ordered_at
          )}
        />
        <Field
          label="Completed"
          value={formatReportDate(
            report.order?.completed_at
          )}
        />
      </Paper>
    </Grid>
  </Grid>
);

export default ReportPatientOrderSection;
