import {
  Chip,
  Grid,
  Typography,
} from '@mui/material';

import type {
  TestResultStatus,
} from '../../../types';

import type {
  ResultFormContext,
} from '../types/testResultForm.types';

interface Props {
  context: ResultFormContext;
  status: TestResultStatus;
}

const formatLabel = (
  value: string
): string => (
  value
    .replace(/_/g, ' ')
    .replace(
      /\b\w/g,
      (character) =>
        character.toUpperCase()
    )
);

const statusColor = (
  status: TestResultStatus
) => {
  if (status === 'approved') {
    return 'success' as const;
  }

  if (status === 'rejected') {
    return 'error' as const;
  }

  if (status === 'submitted') {
    return 'info' as const;
  }

  return 'warning' as const;
};

const SummaryField = ({
  label,
  value,
  detail,
}: {
  label: string;
  value: string;
  detail?: string;
}) => (
  <>
    <Typography
      variant="caption"
      color="text.secondary"
    >
      {label}
    </Typography>

    <Typography
      variant="body1"
      sx={{
        fontWeight: 700,
      }}
    >
      {value}
    </Typography>

    {detail && (
      <Typography
        variant="caption"
        color="text.secondary"
      >
        {detail}
      </Typography>
    )}
  </>
);

const ResultContextSummary = ({
  context,
  status,
}: Props) => (
  <Grid
    container
    spacing={2}
    sx={{
      mb: 3,
    }}
  >
    <Grid
      size={{
        xs: 12,
        md: 3,
      }}
    >
      <SummaryField
        label="Order number"
        value={context.orderNumber}
      />
    </Grid>

    <Grid
      size={{
        xs: 12,
        md: 3,
      }}
    >
      <SummaryField
        label="Patient"
        value={context.patientName}
        detail={
          context.medicalRecordNumber
        }
      />
    </Grid>

    <Grid
      size={{
        xs: 12,
        md: 3,
      }}
    >
      <SummaryField
        label="Test"
        value={context.testName}
        detail={context.category}
      />
    </Grid>

    <Grid
      size={{
        xs: 12,
        md: 3,
      }}
    >
      <Typography
        variant="caption"
        color="text.secondary"
        sx={{
          display: 'block',
          mb: 0.75,
        }}
      >
        Result status
      </Typography>

      <Chip
        size="small"
        color={statusColor(status)}
        label={formatLabel(status)}
      />
    </Grid>
  </Grid>
);

export default ResultContextSummary;
