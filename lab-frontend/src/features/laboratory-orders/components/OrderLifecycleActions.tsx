import { useState } from 'react';
import {
  Alert,
  Box,
  Button,
  Paper,
  Typography,
} from '@mui/material';
import type {
  LaboratoryOrderResource,
  LaboratoryOrderStatus,
} from '../types/laboratoryOrder.types';
import {
  type OrderTransitionStatus,
  useOrderStatusWorkflow,
} from '../hooks/useOrderStatusWorkflow';
import OrderStatusConfirmDialog from './OrderStatusConfirmDialog';

interface Props {
  order: LaboratoryOrderResource;
  canManage: boolean;
  onChanged: () => void;
}

const availableActions: Record<
  LaboratoryOrderStatus,
  OrderTransitionStatus[]
> = {
  pending: ['cancelled'],
  sample_collected: ['in_progress', 'cancelled'],
  in_progress: ['completed'],
  completed: [],
  cancelled: [],
};

const labels: Record<OrderTransitionStatus, string> = {
  in_progress: 'Begin processing',
  completed: 'Complete order',
  cancelled: 'Cancel order',
};

const OrderLifecycleActions = ({ order, canManage, onChanged }: Props) => {
  const [selected, setSelected] = useState<OrderTransitionStatus | null>(null);
  const workflow = useOrderStatusWorkflow(onChanged);
  const actions = availableActions[order.status];

  if (!canManage || actions.length === 0) return null;

  const confirm = async () => {
    if (!selected) return;
    const succeeded = await workflow.transition(order.uuid, selected);
    if (succeeded) setSelected(null);
  };

  return (
    <Paper variant="outlined" sx={{ p: 2, mb: 2.5, borderRadius: 3 }}>
      <Box sx={{
        display: 'flex',
        flexDirection: { xs: 'column', sm: 'row' },
        justifyContent: 'space-between',
        alignItems: { xs: 'stretch', sm: 'center' },
        gap: 1.5,
      }}>
        <Box>
          <Typography variant="subtitle1" sx={{ fontWeight: 720 }}>
            Order lifecycle
          </Typography>
          <Typography variant="body2" color="text.secondary">
            Use only the next transition permitted by the laboratory workflow.
          </Typography>
        </Box>
        <Box sx={{ display: 'flex', gap: 1, flexWrap: 'wrap' }}>
          {actions.map((status) => (
            <Button
              key={status}
              variant={status === 'cancelled' ? 'outlined' : 'contained'}
              color={status === 'cancelled' ? 'error' : 'primary'}
              disabled={workflow.changingTo !== null}
              onClick={() => setSelected(status)}
            >
              {labels[status]}
            </Button>
          ))}
        </Box>
      </Box>
      {workflow.error && (
        <Alert severity="error" onClose={workflow.clearError} sx={{ mt: 1.5 }}>
          {workflow.error}
        </Alert>
      )}
      <OrderStatusConfirmDialog
        status={selected}
        busy={workflow.changingTo !== null}
        onClose={() => setSelected(null)}
        onConfirm={() => void confirm()}
      />
    </Paper>
  );
};

export default OrderLifecycleActions;
