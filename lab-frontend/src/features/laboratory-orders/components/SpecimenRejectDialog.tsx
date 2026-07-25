import { useEffect, useState } from 'react';
import {
  Button,
  Dialog,
  DialogActions,
  DialogContent,
  DialogTitle,
  TextField,
  Typography,
} from '@mui/material';
import type { LaboratoryOrderSpecimen } from '../types/laboratoryOrder.types';

interface Props {
  specimen: LaboratoryOrderSpecimen | null;
  submitting: boolean;
  onClose: () => void;
  onSubmit: (reason: string) => Promise<boolean>;
}

const SpecimenRejectDialog = ({
  specimen,
  submitting,
  onClose,
  onSubmit,
}: Props) => {
  const [reason, setReason] = useState('');

  useEffect(() => {
    if (specimen) setReason('');
  }, [specimen]);

  const submit = async () => {
    if (!reason.trim()) return;
    if (await onSubmit(reason)) onClose();
  };

  return (
    <Dialog open={Boolean(specimen)} onClose={submitting ? undefined : onClose} fullWidth maxWidth="sm">
      <DialogTitle>Reject specimen</DialogTitle>
      <DialogContent dividers>
        <Typography variant="body2" sx={{ mb: 1.5 }}>
          {specimen?.accession_number} · {specimen?.specimen_type}
        </Typography>
        <TextField
          autoFocus
          required
          fullWidth
          multiline
          minRows={3}
          label="Rejection reason"
          value={reason}
          onChange={(event) => setReason(event.target.value)}
          slotProps={{ htmlInput: { maxLength: 2000 } }}
        />
      </DialogContent>
      <DialogActions>
        <Button onClick={onClose} disabled={submitting}>Cancel</Button>
        <Button
          color="error"
          variant="contained"
          disabled={submitting || !reason.trim()}
          onClick={() => void submit()}
        >
          Reject specimen
        </Button>
      </DialogActions>
    </Dialog>
  );
};

export default SpecimenRejectDialog;
