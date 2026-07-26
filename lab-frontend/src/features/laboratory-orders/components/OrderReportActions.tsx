import {
  Box,
  Button,
  Typography,
} from '@mui/material';

import DescriptionRounded from
  '@mui/icons-material/DescriptionRounded';

import type {
  LaboratoryOrderResource,
} from '../types/laboratoryOrder.types';

import type {
  ReportAvailability,
} from '../hooks/useOrderReportActions';

interface Props {
  order: LaboratoryOrderResource;
  canGenerate: boolean;
  availability: ReportAvailability;
  action: 'view' | 'generate' | null;
  onView: () => void;
  onGenerate: () => void;
}

const OrderReportActions = ({
  order,
  canGenerate,
  availability,
  action,
  onView,
  onGenerate,
}: Props) => {
  if (order.status !== 'completed') {
    return null;
  }

  const message = availability === 'issued'
    ? 'Open the immutable report issued for this order.'
    : availability === 'missing'
      ? 'No laboratory report has been issued for this order.'
      : availability === 'error'
        ? 'Report availability could not be confirmed.'
        : 'Checking whether a report has already been issued.';

  return (
    <Box
      sx={{
        p: 2,
        mb: 2.5,
        border: '1px solid',
        borderColor: 'divider',
        borderRadius: 3,
        display: 'flex',
        flexDirection: {
          xs: 'column',
          sm: 'row',
        },
        justifyContent: 'space-between',
        alignItems: {
          xs: 'stretch',
          sm: 'center',
        },
        gap: 1.5,
      }}
    >
      <Box>
        <Typography
          variant="subtitle1"
          sx={{ fontWeight: 720 }}
        >
          Laboratory report
        </Typography>

        <Typography
          variant="body2"
          color="text.secondary"
        >
          {message}
        </Typography>
      </Box>

      <Box
        sx={{
          display: 'flex',
          gap: 1,
          alignItems: 'center',
        }}
      >
        {availability === 'checking' && (
          <Button
            variant="outlined"
            disabled
          >
            Checking report...
          </Button>
        )}

        {availability === 'issued' && (
          <Button
            variant="outlined"
            startIcon={
              <DescriptionRounded />
            }
            disabled={action !== null}
            onClick={onView}
          >
            View report
          </Button>
        )}

        {availability === 'missing' &&
          canGenerate && (
            <Button
              variant="contained"
              disabled={action !== null}
              onClick={onGenerate}
            >
              Generate report
            </Button>
          )}

        {availability === 'missing' &&
          !canGenerate && (
            <Typography
              variant="caption"
              color="text.secondary"
            >
              Awaiting authorised issuance
            </Typography>
          )}
      </Box>
    </Box>
  );
};

export default OrderReportActions;