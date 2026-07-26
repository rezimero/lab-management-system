import type { ReactElement } from 'react';

import {
  Chip,
  alpha,
  useTheme,
} from '@mui/material';

import {
  Cancel as RejectedIcon,
  CheckCircle as ApprovedIcon,
  HourglassEmpty as SubmittedIcon,
  Pending as DraftIcon,
} from '@mui/icons-material';

import type { TestResultStatus } from '../../../types';

type StatusChipConfig = {
  label: string;
  color: 'success' | 'warning' | 'info' | 'error';
  icon: ReactElement;
};

const statusChipConfig: Record<
  TestResultStatus,
  StatusChipConfig
> = {
  draft: {
    label: 'Draft',
    color: 'warning',
    icon: <DraftIcon sx={{ fontSize: 14 }} />,
  },
  submitted: {
    label: 'Submitted',
    color: 'info',
    icon: <SubmittedIcon sx={{ fontSize: 14 }} />,
  },
  approved: {
    label: 'Approved',
    color: 'success',
    icon: <ApprovedIcon sx={{ fontSize: 14 }} />,
  },
  rejected: {
    label: 'Rejected',
    color: 'error',
    icon: <RejectedIcon sx={{ fontSize: 14 }} />,
  },
};

interface TestResultStatusChipProps {
  status: TestResultStatus;
}

export const TestResultStatusChip = ({
  status,
}: TestResultStatusChipProps) => {
  const theme = useTheme();
  const config = statusChipConfig[status];

  return (
    <Chip
      icon={config.icon}
      label={config.label}
      color={config.color}
      size="small"
      sx={{
        fontWeight: 600,
        '& .MuiChip-label': { px: 1.5 },
        ...(status === 'approved' && {
          background: `linear-gradient(135deg, ${alpha(
            theme.palette.success.main,
            0.15
          )}, ${alpha(
            theme.palette.success.light,
            0.05
          )})`,
          border: `1px solid ${alpha(
            theme.palette.success.main,
            0.3
          )}`,
        }),
      }}
    />
  );
};