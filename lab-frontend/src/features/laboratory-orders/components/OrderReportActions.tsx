import { Box, Button, Typography } from '@mui/material';
import DescriptionRounded from '@mui/icons-material/DescriptionRounded';
import type { LaboratoryOrderResource } from '../types/laboratoryOrder.types';

interface Props {
  order: LaboratoryOrderResource;
  canGenerate: boolean;
  action: 'view' | 'generate' | null;
  onView: () => void;
  onGenerate: () => void;
}

const OrderReportActions = ({
  order,
  canGenerate,
  action,
  onView,
  onGenerate,
}: Props) => {
  if (order.status !== 'completed') return null;
  return (
    <Box sx={{
      p: 2,
      mb: 2.5,
      border: '1px solid',
      borderColor: 'divider',
      borderRadius: 3,
      display: 'flex',
      flexDirection: { xs: 'column', sm: 'row' },
      justifyContent: 'space-between',
      alignItems: { xs: 'stretch', sm: 'center' },
      gap: 1.5,
    }}>
      <Box>
        <Typography variant="subtitle1" sx={{ fontWeight: 720 }}>Laboratory report</Typography>
        <Typography variant="body2" color="text.secondary">
          Open the issued report or generate it when no report exists yet.
        </Typography>
      </Box>
      <Box sx={{ display: 'flex', gap: 1 }}>
        <Button
          variant="outlined"
          startIcon={<DescriptionRounded />}
          disabled={action !== null}
          onClick={onView}
        >
          View report
        </Button>
        {canGenerate && (
          <Button
            variant="contained"
            disabled={action !== null}
            onClick={onGenerate}
          >
            Generate report
          </Button>
        )}
      </Box>
    </Box>
  );
};

export default OrderReportActions;
