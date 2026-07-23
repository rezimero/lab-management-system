import RefreshRounded from '@mui/icons-material/RefreshRounded';

import {
  Alert,
  Box,
  Button,
  Skeleton,
} from '@mui/material';

interface TenantManagementErrorProps {
  onRetry: () => void;
}

export const TenantManagementLoading = () => {
  return (
    <Box
      sx={{
        display: 'grid',
        gap: 2.5,
      }}
    >
      <Skeleton
        variant="rounded"
        height={190}
        sx={{
          borderRadius: 4,
        }}
      />

      {[0, 1, 2].map((item) => (
        <Skeleton
          key={item}
          variant="rounded"
          height={125}
          sx={{
            borderRadius: 4,
          }}
        />
      ))}
    </Box>
  );
};

export const TenantManagementError = ({
  onRetry,
}: TenantManagementErrorProps) => {
  return (
    <Box
      sx={{
        minHeight: 480,
        display: 'grid',
        placeItems: 'center',
      }}
    >
      <Alert
        severity="error"
        action={
          <Button
            color="inherit"
            startIcon={
              <RefreshRounded />
            }
            onClick={onRetry}
          >
            Retry
          </Button>
        }
        sx={{
          width: '100%',
          maxWidth: 760,
        }}
      >
        Rezhel could not load the tenant
        portfolio.
      </Alert>
    </Box>
  );
};
