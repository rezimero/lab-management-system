import { useEffect, useState } from 'react';
import {
  Alert,
  Box,
  Button,
  Dialog,
  DialogActions,
  DialogContent,
  DialogTitle,
  TextField,
} from '@mui/material';
import type {
  LaboratoryOrderSpecimen,
  ReplaceSpecimenPayload,
} from '../types/laboratoryOrder.types';

interface Props {
  specimen: LaboratoryOrderSpecimen | null;
  submitting: boolean;
  onClose: () => void;
  onSubmit: (payload: ReplaceSpecimenPayload) => Promise<boolean>;
}

const SpecimenReplaceDialog = ({
  specimen,
  submitting,
  onClose,
  onSubmit,
}: Props) => {
  const [specimenType, setSpecimenType] = useState('');
  const [containerType, setContainerType] = useState('');
  const [collectionSite, setCollectionSite] = useState('');
  const [volume, setVolume] = useState('');
  const [volumeUnit, setVolumeUnit] = useState('');
  const [notes, setNotes] = useState('');
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!specimen) return;
    setSpecimenType(specimen.specimen_type ?? '');
    setContainerType(specimen.container_type ?? '');
    setCollectionSite(specimen.collection_site ?? '');
    setVolume(specimen.volume === null ? '' : String(specimen.volume));
    setVolumeUnit(specimen.volume_unit ?? '');
    setNotes('');
    setError(null);
  }, [specimen]);

  const submit = async () => {
    const numericVolume = volume.trim() ? Number(volume) : undefined;
    if (numericVolume !== undefined && (!Number.isFinite(numericVolume) || numericVolume <= 0)) {
      setError('Replacement volume must be greater than zero.');
      return;
    }
    if (numericVolume !== undefined && !volumeUnit.trim()) {
      setError('A volume unit is required when volume is provided.');
      return;
    }
    setError(null);
    const payload: ReplaceSpecimenPayload = {
      ...(specimenType.trim() ? { specimen_type: specimenType.trim() } : {}),
      ...(containerType.trim() ? { container_type: containerType.trim() } : {}),
      ...(collectionSite.trim() ? { collection_site: collectionSite.trim() } : {}),
      ...(numericVolume !== undefined ? { volume: numericVolume } : {}),
      ...(volumeUnit.trim() ? { volume_unit: volumeUnit.trim() } : {}),
      ...(notes.trim() ? { notes: notes.trim() } : {}),
    };
    if (await onSubmit(payload)) onClose();
  };

  return (
    <Dialog open={Boolean(specimen)} onClose={submitting ? undefined : onClose} fullWidth maxWidth="sm">
      <DialogTitle>Collect replacement specimen</DialogTitle>
      <DialogContent dividers>
        {error && <Alert severity="error" sx={{ mb: 1.5 }}>{error}</Alert>}
        <Box sx={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 1.25 }}>
          <TextField label="Replacement specimen type" size="small" value={specimenType} onChange={(event) => setSpecimenType(event.target.value)} />
          <TextField label="Replacement container type" size="small" value={containerType} onChange={(event) => setContainerType(event.target.value)} />
          <TextField label="Replacement collection site" size="small" value={collectionSite} onChange={(event) => setCollectionSite(event.target.value)} />
          <Box sx={{ display: 'grid', gridTemplateColumns: '1fr 100px', gap: 1 }}>
            <TextField label="Replacement volume" type="number" size="small" value={volume} onChange={(event) => setVolume(event.target.value)} />
            <TextField label="Unit" size="small" value={volumeUnit} onChange={(event) => setVolumeUnit(event.target.value)} />
          </Box>
        </Box>
        <TextField fullWidth multiline minRows={2} label="Replacement notes" value={notes} onChange={(event) => setNotes(event.target.value)} sx={{ mt: 1.25 }} />
      </DialogContent>
      <DialogActions>
        <Button onClick={onClose} disabled={submitting}>Cancel</Button>
        <Button variant="contained" onClick={() => void submit()} disabled={submitting}>Save replacement</Button>
      </DialogActions>
    </Dialog>
  );
};

export default SpecimenReplaceDialog;
