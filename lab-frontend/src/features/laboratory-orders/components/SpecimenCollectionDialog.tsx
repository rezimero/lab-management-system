import { useEffect, useState } from 'react';
import {
  Alert,
  Button,
  Dialog,
  DialogActions,
  DialogContent,
  DialogTitle,
} from '@mui/material';
import type {
  CollectSpecimensPayload,
  LaboratoryOrderItemResource,
} from '../types/laboratoryOrder.types';
import {
  buildCollectionPayload,
  createSpecimenDraft,
  type SpecimenDraft,
} from '../utils/specimenDraft';
import SpecimenDraftFields from './SpecimenDraftFields';

interface Props {
  open: boolean;
  items: LaboratoryOrderItemResource[];
  submitting: boolean;
  onClose: () => void;
  onSubmit: (payload: CollectSpecimensPayload) => Promise<boolean>;
}

const SpecimenCollectionDialog = ({
  open,
  items,
  submitting,
  onClose,
  onSubmit,
}: Props) => {
  const activeItems = items.filter((item) => item.status !== 'cancelled');
  const activeUuids = activeItems.map((item) => item.uuid);
  const [drafts, setDrafts] = useState<SpecimenDraft[]>([]);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (open) {
      setDrafts([createSpecimenDraft(activeUuids)]);
      setError(null);
    }
  }, [open]);

  const updateDraft = (
    index: number,
    nextDraft: SpecimenDraft
  ) => setDrafts((current) => current.map(
    (draft, draftIndex) => draftIndex === index ? nextDraft : draft
  ));

  const submit = async () => {
    const result = buildCollectionPayload(drafts, activeUuids);
    if (!result.payload) {
      setError(result.error ?? 'Specimen details are incomplete.');
      return;
    }
    setError(null);
    if (await onSubmit(result.payload)) onClose();
  };

  return (
    <Dialog open={open} onClose={submitting ? undefined : onClose} fullWidth maxWidth="md">
      <DialogTitle>Collect specimens</DialogTitle>
      <DialogContent dividers>
        {error && <Alert severity="error" sx={{ mb: 1.5 }}>{error}</Alert>}
        {drafts.map((draft, index) => (
          <SpecimenDraftFields
            key={draft.key}
            draft={draft}
            index={index}
            items={activeItems}
            canRemove={drafts.length > 1}
            onChange={(nextDraft) => updateDraft(index, nextDraft)}
            onRemove={() => setDrafts((current) => current.filter(
              (_, draftIndex) => draftIndex !== index
            ))}
          />
        ))}
        <Button
          variant="outlined"
          onClick={() => setDrafts((current) => [
            ...current,
            createSpecimenDraft(),
          ])}
        >
          Add another specimen
        </Button>
      </DialogContent>
      <DialogActions>
        <Button onClick={onClose} disabled={submitting}>Cancel</Button>
        <Button variant="contained" onClick={() => void submit()} disabled={submitting}>
          Save specimens
        </Button>
      </DialogActions>
    </Dialog>
  );
};

export default SpecimenCollectionDialog;
