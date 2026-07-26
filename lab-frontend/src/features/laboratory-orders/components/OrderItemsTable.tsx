import {
  Button,
  Chip,
  Paper,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  Typography,
} from '@mui/material';
import PlayArrowRounded from '@mui/icons-material/PlayArrowRounded';
import type {
  LaboratoryOrderItemResource,
  LaboratoryOrderStatus,
} from '../types/laboratoryOrder.types';
import { formatOrderLabel } from '../utils/orderDisplay';

interface Props {
  orderUuid: string;
  orderStatus: LaboratoryOrderStatus;
  items: LaboratoryOrderItemResource[];
  canCreateResult: boolean;
  startingUuid: string | null;
  onStart: (uuid: string) => void;
  onOpenResult: (uuid: string) => void;
  onEnterResult: (orderUuid: string, itemUuid: string) => void;
}

const ItemAction = ({
  item,
  orderUuid,
  orderStatus,
  canCreateResult,
  startingUuid,
  onStart,
  onOpenResult,
  onEnterResult,
}: Props & { item: LaboratoryOrderItemResource }) => {
  if (item.test_result?.uuid) {
    return <Button size="small" onClick={() => onOpenResult(item.test_result!.uuid)}>Open result</Button>;
  }
  if (!canCreateResult) return <Typography variant="caption">No action</Typography>;
  if (orderStatus === 'in_progress' && item.status === 'pending') {
    return (
      <Button
        size="small"
        startIcon={<PlayArrowRounded />}
        disabled={startingUuid === item.uuid}
        onClick={() => onStart(item.uuid)}
      >
        Start test
      </Button>
    );
  }
  if (orderStatus === 'in_progress' && item.status === 'in_progress') {
    return <Button size="small" onClick={() => onEnterResult(orderUuid, item.uuid)}>Enter result</Button>;
  }
  return <Typography variant="caption">No action</Typography>;
};

const OrderItemsTable = (props: Props) => (
  <TableContainer component={Paper} variant="outlined" sx={{ mb: 2.5 }}>
    <Table>
      <TableHead>
        <TableRow>
          <TableCell>Test</TableCell>
          <TableCell>Category</TableCell>
          <TableCell>Turnaround</TableCell>
          <TableCell>Item status</TableCell>
          <TableCell>Result status</TableCell>
          <TableCell align="right">Action</TableCell>
        </TableRow>
      </TableHead>
      <TableBody>
        {props.items.map((item) => (
          <TableRow key={item.uuid}>
            <TableCell>
              <Typography variant="body2" sx={{ fontWeight: 700 }}>{item.test_name}</Typography>
              <Typography variant="caption" color="text.secondary">
                {Array.isArray(item.parameters) ? `${item.parameters.length} parameter(s)` : 'No parameter snapshot'}
              </Typography>
            </TableCell>
            <TableCell>{item.category ?? 'Not recorded'}</TableCell>
            <TableCell>{item.turnaround_time_hours ? `${item.turnaround_time_hours} hour(s)` : 'Not recorded'}</TableCell>
            <TableCell><Chip size="small" label={formatOrderLabel(item.status)} /></TableCell>
            <TableCell>{item.test_result?.status ? formatOrderLabel(item.test_result.status) : 'Not entered'}</TableCell>
            <TableCell align="right"><ItemAction {...props} item={item} /></TableCell>
          </TableRow>
        ))}
      </TableBody>
    </Table>
  </TableContainer>
);

export default OrderItemsTable;
