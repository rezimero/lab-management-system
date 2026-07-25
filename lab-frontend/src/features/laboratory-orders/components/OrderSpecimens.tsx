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
import type { LaboratoryOrderSpecimen } from '../types/laboratoryOrder.types';
import {
  formatOrderDate,
  formatOrderLabel,
} from '../utils/orderDisplay';

interface Props {
  specimens: LaboratoryOrderSpecimen[];
}

const OrderSpecimens = ({ specimens }: Props) => (
  <>
    <Typography variant="h6" sx={{ mb: 1.25, fontWeight: 720 }}>
      Specimens
    </Typography>
    {specimens.length === 0 ? (
      <Alert severity="info" sx={{ mb: 2.5 }}>
        No specimens have been recorded for this order.
      </Alert>
    ) : (
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
            </TableRow>
          </TableHead>
          <TableBody>
            {specimens.map((specimen) => (
              <TableRow key={specimen.uuid}>
                <TableCell>{specimen.accession_number}</TableCell>
                <TableCell>{specimen.specimen_type}</TableCell>
                <TableCell>{specimen.container_type ?? 'Not recorded'}</TableCell>
                <TableCell><Chip size="small" label={formatOrderLabel(specimen.status)} /></TableCell>
                <TableCell>{formatOrderDate(specimen.collected_at)}</TableCell>
                <TableCell>{formatOrderDate(specimen.received_at)}</TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </TableContainer>
    )}
  </>
);

export default OrderSpecimens;
