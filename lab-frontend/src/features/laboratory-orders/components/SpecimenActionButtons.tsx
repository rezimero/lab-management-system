import { Button, Stack, Typography } from '@mui/material';
import type {
  LaboratoryOrderSpecimen,
  LaboratoryOrderStatus,
} from '../types/laboratoryOrder.types';

interface Props {
  orderStatus: LaboratoryOrderStatus;
  specimen: LaboratoryOrderSpecimen;
  canManage: boolean;
  busyUuid: string | null;
  onReceive: (specimen: LaboratoryOrderSpecimen) => void;
  onReject: (specimen: LaboratoryOrderSpecimen) => void;
  onReplace: (specimen: LaboratoryOrderSpecimen) => void;
}

const SpecimenActionButtons = ({
  orderStatus,
  specimen,
  canManage,
  busyUuid,
  onReceive,
  onReject,
  onReplace,
}: Props) => {
  if (!canManage || ['completed', 'cancelled'].includes(orderStatus)) {
    return <Typography variant="caption">No action</Typography>;
  }

  const busy = busyUuid === specimen.uuid;
  const canReceive = specimen.status === 'collected';
  const canReject = ['collected', 'received'].includes(specimen.status);
  const canReplace = specimen.status === 'rejected'
    && orderStatus === 'sample_collected'
    && !specimen.replacement;

  if (!canReceive && !canReject && !canReplace) {
    return (
      <Typography variant="caption">
        {specimen.replacement ? 'Replacement recorded' : 'No action'}
      </Typography>
    );
  }

  return (
    <Stack direction="row" spacing={0.5} sx={{ justifyContent: 'flex-end' }}>
      {canReceive && (
        <Button size="small" disabled={busy} onClick={() => onReceive(specimen)}>
          Receive
        </Button>
      )}
      {canReject && (
        <Button color="error" size="small" disabled={busy} onClick={() => onReject(specimen)}>
          Reject
        </Button>
      )}
      {canReplace && (
        <Button size="small" disabled={busy} onClick={() => onReplace(specimen)}>
          Replace
        </Button>
      )}
    </Stack>
  );
};

export default SpecimenActionButtons;
