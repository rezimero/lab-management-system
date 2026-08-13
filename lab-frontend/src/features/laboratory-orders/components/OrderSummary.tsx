import { Box, Divider, Paper, Typography } from '@mui/material';
import type { LaboratoryOrderResource } from '../types/laboratoryOrder.types';
import {
  formatOrderAmount,
  formatOrderDate,
} from '../utils/orderDisplay';

interface Props {
  order: LaboratoryOrderResource;
}

const Field = ({ label, value }: { label: string; value: string }) => (
  <Box>
    <Typography variant="caption" color="text.secondary">{label}</Typography>
    <Typography variant="body2" sx={{ mt: 0.25, fontWeight: 600 }}>{value}</Typography>
  </Box>
);

const OrderSummary = ({ order }: Props) => (
  <Paper variant="outlined" sx={{ p: { xs: 2, md: 2.5 }, mb: 2.5 }}>
    <Typography variant="h6" sx={{ mb: 2, fontWeight: 720 }}>
      Patient and order information
    </Typography>
    <Box sx={{
      display: 'grid',
      gridTemplateColumns: { xs: '1fr', sm: 'repeat(2, 1fr)', lg: 'repeat(4, 1fr)' },
      gap: 2,
    }}>
      <Field label="Patient" value={order.patient?.full_name ?? 'Not recorded'} />
      <Field label="Medical record number" value={order.patient?.medical_record_number ?? 'Not recorded'} />
      <Field label="Referring clinician" value={order.referring_clinician_record?.name ?? order.referring_clinician ?? 'Not recorded'} />
      <Field label="Ordered by" value={order.ordered_by?.name ?? 'Not recorded'} />
      <Field label="Ordered" value={formatOrderDate(order.ordered_at)} />
      <Field label="Sample collected" value={formatOrderDate(order.sample_collected_at)} />
      <Field label="Completed" value={formatOrderDate(order.completed_at)} />
      <Field label="Total amount" value={formatOrderAmount(order.total_amount)} />
    </Box>
    <Divider sx={{ my: 2 }} />
    <Typography variant="caption" color="text.secondary">Clinical notes</Typography>
    <Typography variant="body2" sx={{ mt: 0.5, whiteSpace: 'pre-wrap' }}>
      {order.clinical_notes ?? 'No clinical notes were recorded.'}
    </Typography>
  </Paper>
);

export default OrderSummary;
