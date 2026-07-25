import {
  Alert,
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
import type {
  LaboratoryOrderSpecimen,
  LaboratoryOrderStatus,
} from '../types/laboratoryOrder.types';
import {
  formatOrderDate,
  formatOrderLabel,
} from '../utils/orderDisplay';
import SpecimenActionButtons from './SpecimenActionButtons';

interface Props {
  orderStatus: LaboratoryOrderStatus;
  specimens: LaboratoryOrderSpecimen[];
  canManage: boolean;
  busyUuid: string | null;
  onReceive: (specimen: LaboratoryOrderSpecimen) => void;
  onReject: (specimen: LaboratoryOrderSpecimen) => void;
  onReplace: (specimen: LaboratoryOrderSpecimen) => void;
}

const StatusCell = ({ specimen }: { specimen: LaboratoryOrderSpecimen }) => (
  <>
    <Chip size="small" label={formatOrderLabel(specimen.status)} />
    {specimen.rejection_reason && (
      <Typography variant="caption" color="error" sx={{ display: 'block', mt: 0.5 }}>
        {specimen.rejection_reason}
      </Typography>
    )}
    {specimen.replacement && (
      <Typography variant="caption" color="text.secondary" sx={{ display: 'block', mt: 0.5 }}>
        Replaced by {specimen.replacement.accession_number}
      </Typography>
    )}
  </>
);

const OrderSpecimens = (props: Props) => {
  if (props.specimens.length === 0) {
    return (
      <Alert severity="info" sx={{ mb: 2.5 }}>
        No specimens have been recorded for this order.
      </Alert>
    );
  }

  return (
    <TableContainer component={Paper} variant="outlined" sx={{ mb: 2.5 }}>
      <Table>
        <TableHead>
          <TableRow>
            <TableCell>Accession</TableCell>
            <TableCell>Type</TableCell>
            <TableCell>Container</TableCell>
            <TableCell>Status</TableCell>
            <TableCell>Collected</TableCell>
            <TableCell>Received</TableCell>
            <TableCell align="right">Action</TableCell>
          </TableRow>
        </TableHead>
        <TableBody>
          {props.specimens.map((specimen) => (
            <TableRow key={specimen.uuid}>
              <TableCell>{specimen.accession_number}</TableCell>
              <TableCell>{specimen.specimen_type}</TableCell>
              <TableCell>{specimen.container_type ?? 'Not recorded'}</TableCell>
              <TableCell><StatusCell specimen={specimen} /></TableCell>
              <TableCell>{formatOrderDate(specimen.collected_at)}</TableCell>
              <TableCell>{formatOrderDate(specimen.received_at)}</TableCell>
              <TableCell align="right">
                <SpecimenActionButtons
                  orderStatus={props.orderStatus}
                  specimen={specimen}
                  canManage={props.canManage}
                  busyUuid={props.busyUuid}
                  onReceive={props.onReceive}
                  onReject={props.onReject}
                  onReplace={props.onReplace}
                />
              </TableCell>
            </TableRow>
          ))}
        </TableBody>
      </Table>
    </TableContainer>
  );
};

export default OrderSpecimens;
