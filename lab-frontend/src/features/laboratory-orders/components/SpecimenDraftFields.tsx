import {
  Box,
  Button,
  Checkbox,
  Divider,
  FormControlLabel,
  FormGroup,
  TextField,
  Typography,
} from '@mui/material';
import type { LaboratoryOrderItemResource } from '../types/laboratoryOrder.types';
import type { SpecimenDraft } from '../utils/specimenDraft';

interface Props {
  draft: SpecimenDraft;
  index: number;
  items: LaboratoryOrderItemResource[];
  canRemove: boolean;
  onChange: (draft: SpecimenDraft) => void;
  onRemove: () => void;
}

const SpecimenDraftFields = ({
  draft,
  index,
  items,
  canRemove,
  onChange,
  onRemove,
}: Props) => {
  const update = (
    field: keyof SpecimenDraft,
    value: string | string[]
  ) => onChange({ ...draft, [field]: value } as SpecimenDraft);

  const toggleItem = (
    uuid: string,
    checked: boolean
  ) => update(
    'order_item_uuids',
    checked
      ? [...draft.order_item_uuids, uuid]
      : draft.order_item_uuids.filter((itemUuid) => itemUuid !== uuid)
  );

  return (
    <Box sx={{ py: 1.5 }}>
      <Box sx={{ display: 'flex', justifyContent: 'space-between', mb: 1.25 }}>
        <Typography variant="subtitle1" sx={{ fontWeight: 720 }}>
          Specimen {index + 1}
        </Typography>
        {canRemove && (
          <Button color="error" size="small" onClick={onRemove}>
            Remove
          </Button>
        )}
      </Box>
      <Box sx={{
        display: 'grid',
        gridTemplateColumns: { xs: '1fr', sm: '1fr 1fr' },
        gap: 1.25,
      }}>
        <TextField
          required
          size="small"
          label={`Specimen type ${index + 1}`}
          value={draft.specimen_type}
          onChange={(event) => update('specimen_type', event.target.value)}
        />
        <TextField
          size="small"
          label={`Container type ${index + 1}`}
          value={draft.container_type}
          onChange={(event) => update('container_type', event.target.value)}
        />
        <TextField
          size="small"
          label={`Collection site ${index + 1}`}
          value={draft.collection_site}
          onChange={(event) => update('collection_site', event.target.value)}
        />
        <Box sx={{ display: 'grid', gridTemplateColumns: '1fr 110px', gap: 1 }}>
          <TextField
            size="small"
            type="number"
            label={`Volume ${index + 1}`}
            value={draft.volume}
            onChange={(event) => update('volume', event.target.value)}
          />
          <TextField
            size="small"
            label={`Unit ${index + 1}`}
            value={draft.volume_unit}
            onChange={(event) => update('volume_unit', event.target.value)}
          />
        </Box>
      </Box>
      <TextField
        fullWidth
        multiline
        minRows={2}
        size="small"
        label={`Notes ${index + 1}`}
        value={draft.notes}
        onChange={(event) => update('notes', event.target.value)}
        sx={{ mt: 1.25 }}
      />
      <Typography variant="subtitle2" sx={{ mt: 1.5 }}>
        Tests covered by this specimen
      </Typography>
      <FormGroup>
        {items.map((item) => (
          <FormControlLabel
            key={item.uuid}
            control={(
              <Checkbox
                checked={draft.order_item_uuids.includes(item.uuid)}
                onChange={(event) => toggleItem(item.uuid, event.target.checked)}
              />
            )}
            label={item.test_name}
          />
        ))}
      </FormGroup>
      <Divider sx={{ mt: 1 }} />
    </Box>
  );
};

export default SpecimenDraftFields;
