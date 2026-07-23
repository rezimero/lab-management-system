import {
  Box,
  CircularProgress,
  Stack,
  Typography,
} from '@mui/material';

import {
  RezhelMark,
} from '../brand/LabBrand';

interface AppLoadingScreenProps {
  label?: string;
  compact?: boolean;
}

const AppLoadingScreen = ({
  label = 'Preparing your clinical workspace',
  compact = false,
}: AppLoadingScreenProps) => {
  return (
    <Box
      role="status"
      aria-live="polite"
      aria-label={label}
      sx={{
        position: compact
          ? 'relative'
          : 'fixed',
        inset: compact
          ? 'auto'
          : 0,
        zIndex: compact
          ? 'auto'
          : 1600,
        minHeight: compact
          ? 240
          : '100vh',
        display: 'grid',
        placeItems: 'center',
        px: 3,
        background: compact
          ? 'transparent'
          : `
            radial-gradient(
              circle at 15% 15%,
              rgba(36, 107, 253, 0.11),
              transparent 34%
            ),
            radial-gradient(
              circle at 85% 85%,
              rgba(8, 145, 178, 0.09),
              transparent 32%
            ),
            #F4F7FB
          `,
      }}
    >
      <Stack
        spacing={2.5}
        sx={{
          alignItems: 'center',
          textAlign: 'center',
        }}
      >
        <Box
          sx={{
            position: 'relative',
            width: 82,
            height: 82,
            display: 'grid',
            placeItems: 'center',
          }}
        >
          <CircularProgress
            size={82}
            thickness={2}
            sx={{
              position: 'absolute',
              color: 'primary.main',
              opacity: 0.78,
            }}
          />

          <Box
            sx={{
              display: 'grid',
              placeItems: 'center',
              filter:
                'drop-shadow(0 12px 24px rgba(36, 107, 253, 0.18))',
            }}
          >
            <RezhelMark size={54} />
          </Box>
        </Box>

        <Stack
          spacing={0.65}
          sx={{
            alignItems: 'center',
          }}
        >
          <Typography
            sx={{
              color: 'text.primary',
              fontSize: '1.18rem',
              lineHeight: 1,
              fontWeight: 780,
              letterSpacing: '-0.035em',
            }}
          >
            Rezhel
          </Typography>

          <Typography
            variant="body2"
            color="text.secondary"
          >
            {label}
          </Typography>
        </Stack>
      </Stack>
    </Box>
  );
};

export default AppLoadingScreen;