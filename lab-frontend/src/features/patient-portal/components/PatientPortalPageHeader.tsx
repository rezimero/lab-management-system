import type {
  ReactNode,
} from 'react';

import {
  Box,
  Typography,
} from '@mui/material';

interface PatientPortalPageHeaderProps {
  eyebrow?: string;
  title: string;
  description: string;
  action?: ReactNode;
}

const PatientPortalPageHeader = ({
  eyebrow,
  title,
  description,
  action,
}: PatientPortalPageHeaderProps) => (
  <Box
    sx={{
      display: 'flex',
      gap: 2,
      mb: 3,
      flexDirection: {
        xs: 'column',
        sm: 'row',
      },
      alignItems: {
        xs: 'stretch',
        sm: 'flex-start',
      },
      justifyContent: 'space-between',
    }}
  >
    <Box>
      {eyebrow && (
        <Typography
          sx={{
            mb: 0.6,
            color: 'primary.main',
            fontSize: '0.75rem',
            fontWeight: 740,
            letterSpacing: '0.08em',
            textTransform: 'uppercase',
          }}
        >
          {eyebrow}
        </Typography>
      )}

      <Typography
        variant="h4"
        component="h1"
        sx={{
          fontWeight: 760,
          letterSpacing: '-0.025em',
        }}
      >
        {title}
      </Typography>

      <Typography
        sx={{
          mt: 0.75,
          maxWidth: 720,
          color: 'text.secondary',
          lineHeight: 1.65,
        }}
      >
        {description}
      </Typography>
    </Box>

    {action && (
      <Box
        sx={{
          flexShrink: 0,
        }}
      >
        {action}
      </Box>
    )}
  </Box>
);

export default PatientPortalPageHeader;
