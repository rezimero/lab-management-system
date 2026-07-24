import {
  Paper,
  Typography,
} from '@mui/material';

import type {
  TestResultUserSummary,
} from '../../../types';

import {
  formatResultDateTime,
} from '../utils/testResultDisplay';

interface Props {
  label: string;
  person?: TestResultUserSummary | null;
  timestamp?: string | null;
}

const WorkflowHistoryCard = ({
  label,
  person,
  timestamp,
}: Props) => (
  <Paper
    variant="outlined"
    sx={{
      p: 2,
      height: '100%',
      borderRadius: 2.5,
    }}
  >
    <Typography
      variant="caption"
      color="text.secondary"
      sx={{
        display: 'block',
        mb: 0.75,
        fontWeight: 700,
        textTransform: 'uppercase',
        letterSpacing: '0.05em',
      }}
    >
      {label}
    </Typography>

    <Typography
      variant="body2"
      sx={{
        fontWeight: 700,
      }}
    >
      {person?.name ?? 'Not recorded'}
    </Typography>

    {person?.email && (
      <Typography
        variant="caption"
        color="text.secondary"
        sx={{
          display: 'block',
          mt: 0.25,
          wordBreak: 'break-word',
        }}
      >
        {person.email}
      </Typography>
    )}

    <Typography
      variant="caption"
      color="text.secondary"
      sx={{
        display: 'block',
        mt: 1,
      }}
    >
      {formatResultDateTime(
        timestamp
      )}
    </Typography>
  </Paper>
);

export default WorkflowHistoryCard;
