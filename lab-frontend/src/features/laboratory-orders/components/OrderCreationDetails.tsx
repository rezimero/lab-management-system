import {
  Grid,
  MenuItem,
  TextField,
} from '@mui/material';

import type {
  LaboratoryOrderPriority,
} from '../types/laboratoryOrder.types';

interface Props {
  priority:
    LaboratoryOrderPriority;
  clinicalNotes: string;
  onPriorityChange: (
    value:
      LaboratoryOrderPriority
  ) => void;
  onClinicalNotesChange: (
    value: string
  ) => void;
}

const OrderCreationDetails = ({
  priority,
  clinicalNotes,
  onPriorityChange,
  onClinicalNotesChange,
}: Props) => (
  <Grid
    container
    spacing={2}
  >
    <Grid size={{ xs: 12, md: 4 }}>
      <TextField
        select
        fullWidth
        label="Priority"
        value={priority}
        onChange={(event) => {
          onPriorityChange(
            event.target.value as LaboratoryOrderPriority
          );
        }}
      >
        <MenuItem value="routine">
          Routine
        </MenuItem>
        <MenuItem value="urgent">
          Urgent
        </MenuItem>
        <MenuItem value="stat">
          STAT
        </MenuItem>
      </TextField>
    </Grid>

    <Grid size={{ xs: 12, md: 8 }}>
      <TextField
        fullWidth
        multiline
        minRows={3}
        label="Clinical notes"
        value={clinicalNotes}
        onChange={(event) => {
          onClinicalNotesChange(
            event.target.value
          );
        }}
        slotProps={{
          htmlInput: {
            maxLength: 5000,
          },
        }}
      />
    </Grid>
  </Grid>
);

export default OrderCreationDetails;
