import {
  Box,
  Chip,
  IconButton,
  Typography,
} from '@mui/material';
import ArrowBackRounded from '@mui/icons-material/ArrowBackRounded';
import type { LaboratoryOrderResource } from '../types/laboratoryOrder.types';
import { formatOrderLabel } from '../utils/orderDisplay';

interface Props {
  order: LaboratoryOrderResource;
  onBack: () => void;
}

const OrderDetailHeader = ({ order, onBack }: Props) => (
  <Box sx={{
    mb: 2.5,
    display: 'flex',
    alignItems: 'flex-start',
    justifyContent: 'space-between',
    gap: 2,
  }}>
    <Box sx={{ display: 'flex', gap: 1.25 }}>
      <IconButton aria-label="Back to laboratory orders" onClick={onBack}>
        <ArrowBackRounded />
      </IconButton>
      <Box>
        <Typography variant="h5" sx={{ fontWeight: 760 }}>
          {order.order_number}
        </Typography>
        <Typography variant="body2" color="text.secondary">
          Laboratory order workspace
        </Typography>
      </Box>
    </Box>
    <Box sx={{ display: 'flex', gap: 1 }}>
      <Chip label={formatOrderLabel(order.priority)} />
      <Chip color="primary" variant="outlined" label={formatOrderLabel(order.status)} />
    </Box>
  </Box>
);

export default OrderDetailHeader;
