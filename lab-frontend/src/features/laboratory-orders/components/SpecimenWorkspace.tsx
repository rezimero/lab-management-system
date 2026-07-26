import { useState } from 'react';
import { Alert, Box, Button, Typography } from '@mui/material';
import type {
  LaboratoryOrderResource,
  LaboratoryOrderSpecimen,
} from '../types/laboratoryOrder.types';
import { useSpecimenWorkflow } from '../hooks/useSpecimenWorkflow';
import OrderSpecimens from './OrderSpecimens';
import SpecimenCollectionDialog from './SpecimenCollectionDialog';
import SpecimenRejectDialog from './SpecimenRejectDialog';
import SpecimenReplaceDialog from './SpecimenReplaceDialog';

interface Props {
  order: LaboratoryOrderResource;
  canManage: boolean;
  onChanged: () => void;
}

const SpecimenWorkspace = ({ order, canManage, onChanged }: Props) => {
  const workflow = useSpecimenWorkflow(onChanged);
  const [collectionOpen, setCollectionOpen] = useState(false);
  const [rejecting, setRejecting] = useState<LaboratoryOrderSpecimen | null>(null);
  const [replacing, setReplacing] = useState<LaboratoryOrderSpecimen | null>(null);
  const busyUuid = workflow.action?.uuid ?? null;

  return (
    <>
      <Box sx={{
        mb: 1.25,
        display: 'flex',
        justifyContent: 'space-between',
        alignItems: 'center',
        gap: 1,
      }}>
        <Typography variant="h6" sx={{ fontWeight: 720 }}>
          Specimens
        </Typography>
        {canManage && order.status === 'pending' && (
          <Button variant="contained" onClick={() => setCollectionOpen(true)}>
            Collect specimens
          </Button>
        )}
      </Box>
      {workflow.error && (
        <Alert severity="error" onClose={workflow.clearError} sx={{ mb: 1.5 }}>
          {workflow.error}
        </Alert>
      )}
      <OrderSpecimens
        orderStatus={order.status}
        specimens={order.specimens ?? []}
        canManage={canManage}
        busyUuid={busyUuid}
        onReceive={(specimen) => void workflow.receive(specimen.uuid)}
        onReject={setRejecting}
        onReplace={setReplacing}
      />
      <SpecimenCollectionDialog
        open={collectionOpen}
        items={order.items ?? []}
        submitting={workflow.action?.kind === 'collect'}
        onClose={() => setCollectionOpen(false)}
        onSubmit={(payload) => workflow.collect(order.uuid, payload)}
      />
      <SpecimenRejectDialog
        specimen={rejecting}
        submitting={workflow.action?.kind === 'reject'}
        onClose={() => setRejecting(null)}
        onSubmit={(reason) => workflow.reject(rejecting!.uuid, reason)}
      />
      <SpecimenReplaceDialog
        specimen={replacing}
        submitting={workflow.action?.kind === 'replace'}
        onClose={() => setReplacing(null)}
        onSubmit={(payload) => workflow.replace(replacing!.uuid, payload)}
      />
    </>
  );
};

export default SpecimenWorkspace;
