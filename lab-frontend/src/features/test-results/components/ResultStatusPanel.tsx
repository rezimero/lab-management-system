import {
  Box,
  Chip,
  Typography,
  alpha,
  useTheme,
} from '@mui/material';

import {
  Cancel as CancelIcon,
  CheckCircle as CheckCircleIcon,
  HourglassEmpty as HourglassEmptyIcon,
  Pending as PendingIcon,
} from '@mui/icons-material';

import type {
  TestResultResource,
  TestResultStatus,
} from '../../../types';

import {
  formatResultLabel,
  statusVisualFor,
} from '../utils/testResultDisplay';

const statusIcon = (
  status: TestResultStatus
) => {
  if (status === 'approved') {
    return <CheckCircleIcon />;
  }

  if (status === 'rejected') {
    return <CancelIcon />;
  }

  if (status === 'submitted') {
    return <HourglassEmptyIcon />;
  }

  return <PendingIcon />;
};

interface Props {
  result: TestResultResource;
}

const ResultStatusPanel = ({
  result,
}: Props) => {
  const theme = useTheme();
  const visual = statusVisualFor(
    result.status
  );
  const order = result.order_item?.order;

  return (
    <Box
      sx={{
        mb: 3,
        p: 2,
        display: 'flex',
        flexDirection: {
          xs: 'column',
          sm: 'row',
        },
        justifyContent: 'space-between',
        alignItems: {
          xs: 'flex-start',
          sm: 'center',
        },
        gap: 2,
        borderRadius: 2,
        borderLeft:
          `4px solid ${visual.accent}`,
        backgroundColor: alpha(
          visual.accent,
          0.07
        ),
      }}
    >
      <Box>
        <Typography
          variant="caption"
          color="text.secondary"
          sx={{
            display: 'block',
            mb: 0.75,
          }}
        >
          Workflow status
        </Typography>

        <Chip
          icon={statusIcon(result.status)}
          label={visual.label}
          color={visual.color}
        />

        <Typography
          variant="body2"
          color="text.secondary"
          sx={{
            mt: 1,
          }}
        >
          {visual.description}
        </Typography>
      </Box>

      <Box
        sx={{
          textAlign: {
            xs: 'left',
            sm: 'right',
          },
        }}
      >
        <Typography
          variant="caption"
          color="text.secondary"
        >
          Laboratory order
        </Typography>

        <Typography
          variant="body1"
          sx={{
            fontWeight: 750,
          }}
        >
          {order?.order_number ??
            'Not recorded'}
        </Typography>

        <Typography
          variant="caption"
          color="text.secondary"
        >
          Priority:{' '}
          {formatResultLabel(
            order?.priority
          )}
        </Typography>
      </Box>
    </Box>
  );
};

export default ResultStatusPanel;
