import InboxRounded from
  '@mui/icons-material/InboxRounded';

import {
  Box,
  Button,
  Paper,
  Typography,
} from '@mui/material';

interface PatientPortalEmptyStateProps {
  title: string;
  description: string;
  actionLabel?: string;
  onAction?: () => void;
}

const PatientPortalEmptyState = ({
  title,
  description,
  actionLabel,
  onAction,
}: PatientPortalEmptyStateProps) => (
  <Paper
    variant="outlined"
    sx={{
      px: {
        xs: 2.5,
        sm: 4,
      },
      py: {
        xs: 5,
        sm: 6,
      },
      textAlign: 'center',
      borderStyle: 'dashed',
      backgroundColor: 'background.paper',
    }}
  >
    <Box
      sx={{
        width: 52,
        height: 52,
        mx: 'auto',
        mb: 2,
        display: 'grid',
        placeItems: 'center',
        borderRadius: '16px',
        color: 'primary.main',
        backgroundColor:
          'rgba(36, 107, 253, 0.08)',
      }}
    >
      <InboxRounded />
    </Box>

    <Typography
      variant="h6"
      sx={{
        mb: 0.75,
        fontWeight: 720,
      }}
    >
      {title}
    </Typography>

    <Typography
      sx={{
        mx: 'auto',
        maxWidth: 520,
        color: 'text.secondary',
      }}
    >
      {description}
    </Typography>

    {actionLabel && onAction && (
      <Button
        variant="outlined"
        onClick={onAction}
        sx={{
          mt: 2.5,
        }}
      >
        {actionLabel}
      </Button>
    )}
  </Paper>
);

export default PatientPortalEmptyState;
