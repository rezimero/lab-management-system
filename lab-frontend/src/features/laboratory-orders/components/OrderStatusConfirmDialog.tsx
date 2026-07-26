import {
  Button,
  Dialog,
  DialogActions,
  DialogContent,
  DialogContentText,
  DialogTitle,
} from '@mui/material';
import type { OrderTransitionStatus } from '../hooks/useOrderStatusWorkflow';

interface Props {
  status: OrderTransitionStatus | null;
  busy: boolean;
  onClose: () => void;
  onConfirm: () => void;
}

const copy = {
  in_progress: {
    title: 'Begin processing?',
    body: 'Every active test must have a received specimen before processing can begin.',
    action: 'Begin processing',
  },
  completed: {
    title: 'Complete this order?',
    body: 'Completion requires every active test to have a completed and approved result.',
    action: 'Complete order',
  },
  cancelled: {
    title: 'Cancel this order?',
    body: 'A cancelled order is terminal and cannot return to active processing.',
    action: 'Cancel order',
  },
} as const;

const OrderStatusConfirmDialog = ({
  status,
  busy,
  onClose,
  onConfirm,
}: Props) => {
  const content = status ? copy[status] : null;
  return (
    <Dialog open={Boolean(status)} onClose={busy ? undefined : onClose}>
      <DialogTitle>{content?.title}</DialogTitle>
      <DialogContent>
        <DialogContentText>{content?.body}</DialogContentText>
      </DialogContent>
      <DialogActions>
        <Button onClick={onClose} disabled={busy}>Keep order</Button>
        <Button
          variant="contained"
          color={status === 'cancelled' ? 'error' : 'primary'}
          onClick={onConfirm}
          disabled={busy}
        >
          {busy ? 'Updating…' : content?.action}
        </Button>
      </DialogActions>
    </Dialog>
  );
};

export default OrderStatusConfirmDialog;
