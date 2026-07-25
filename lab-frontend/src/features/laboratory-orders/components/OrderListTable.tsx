import {
  Alert,
  Chip,
  CircularProgress,
  Paper,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  Typography,
} from '@mui/material';
import type { LaboratoryOrderResource } from '../types/laboratoryOrder.types';
import {
  formatOrderDate,
  formatOrderLabel,
} from '../utils/orderDisplay';

interface Props {
  orders: LaboratoryOrderResource[];
  loading: boolean;
  error: string | null;
  onOpen: (uuid: string) => void;
}

const OrderListTable = ({
  orders,
  loading,
  error,
  onOpen,
}: Props) => {
  if (loading) {
    return (
      <Paper variant="outlined" sx={{ py: 8, display: 'grid', placeItems: 'center' }}>
        <CircularProgress />
      </Paper>
    );
  }
  if (error) return <Alert severity="error">{error}</Alert>;
  if (orders.length === 0) {
    return <Alert severity="info">No laboratory orders match the current filters.</Alert>;
  }

  return (
    <TableContainer component={Paper} variant="outlined">
      <Table>
        <TableHead>
          <TableRow>
            <TableCell>Order number</TableCell>
            <TableCell>Patient</TableCell>
            <TableCell>Priority</TableCell>
            <TableCell>Status</TableCell>
            <TableCell>Tests</TableCell>
            <TableCell>Ordered</TableCell>
          </TableRow>
        </TableHead>
        <TableBody>
          {orders.map((order) => (
            <TableRow
              key={order.uuid}
              hover
              tabIndex={0}
              role="link"
              onClick={() => onOpen(order.uuid)}
              onKeyDown={(event) => {
                if (event.key === 'Enter' || event.key === ' ') onOpen(order.uuid);
              }}
              sx={{ cursor: 'pointer' }}
            >
              <TableCell>
                <Typography variant="body2" sx={{ fontWeight: 720 }}>
                  {order.order_number}
                </Typography>
              </TableCell>
              <TableCell>
                {order.patient?.full_name ?? 'Not recorded'}
                <Typography variant="caption" sx={{ display: 'block' }} color="text.secondary">
                  {order.patient?.medical_record_number ?? 'No MRN'}
                </Typography>
              </TableCell>
              <TableCell>
                <Chip size="small" label={formatOrderLabel(order.priority)} />
              </TableCell>
              <TableCell>
                <Chip size="small" variant="outlined" label={formatOrderLabel(order.status)} />
              </TableCell>
              <TableCell>{order.items?.length ?? 0}</TableCell>
              <TableCell>{formatOrderDate(order.ordered_at)}</TableCell>
            </TableRow>
          ))}
        </TableBody>
      </Table>
    </TableContainer>
  );
};

export default OrderListTable;
