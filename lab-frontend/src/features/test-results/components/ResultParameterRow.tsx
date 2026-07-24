import {
  Grid,
  IconButton,
  MenuItem,
  Paper,
  TextField,
} from '@mui/material';

import {
  Delete as DeleteIcon,
} from '@mui/icons-material';

import {
  RESULT_FLAGS,
  type ResultFormRow,
} from '../types/testResultForm.types';

interface Props {
  index: number;
  row: ResultFormRow;
  disabled: boolean;
  onChange: (
    index: number,
    field: keyof ResultFormRow,
    value: string
  ) => void;
  onRemove: (
    index: number
  ) => void;
}

const formatLabel = (
  value: string
): string => (
  value.charAt(0).toUpperCase() +
  value.slice(1)
);

const ResultParameterRow = ({
  index,
  row,
  disabled,
  onChange,
  onRemove,
}: Props) => (
  <Paper
    variant="outlined"
    sx={{
      p: 2,
      mb: 2,
      borderRadius: 2,
    }}
  >
    <Grid
      container
      spacing={2}
      sx={{
        alignItems: 'center',
      }}
    >
      <Grid size={{ xs: 12, md: 3 }}>
        <TextField
          fullWidth
          required
          label="Parameter name"
          value={row.name}
          disabled={disabled}
          onChange={(event) => {
            onChange(
              index,
              'name',
              event.target.value
            );
          }}
        />
      </Grid>

      <Grid size={{ xs: 12, sm: 6, md: 2 }}>
        <TextField
          fullWidth
          label="Value"
          value={row.value}
          disabled={disabled}
          onChange={(event) => {
            onChange(
              index,
              'value',
              event.target.value
            );
          }}
        />
      </Grid>

      <Grid size={{ xs: 12, sm: 6, md: 1 }}>
        <TextField
          fullWidth
          label="Unit"
          value={row.unit}
          disabled={disabled}
          onChange={(event) => {
            onChange(
              index,
              'unit',
              event.target.value
            );
          }}
        />
      </Grid>

      <Grid size={{ xs: 12, sm: 6, md: 2 }}>
        <TextField
          fullWidth
          label="Reference range"
          value={row.reference_range}
          disabled={disabled}
          onChange={(event) => {
            onChange(
              index,
              'reference_range',
              event.target.value
            );
          }}
        />
      </Grid>

      <Grid size={{ xs: 12, sm: 6, md: 2 }}>
        <TextField
          select
          fullWidth
          label="Flag"
          value={row.flag}
          disabled={disabled}
          onChange={(event) => {
            onChange(
              index,
              'flag',
              event.target.value
            );
          }}
        >
          <MenuItem value="">
            Not flagged
          </MenuItem>

          {RESULT_FLAGS.map((flag) => (
            <MenuItem
              key={flag}
              value={flag}
            >
              {formatLabel(flag)}
            </MenuItem>
          ))}
        </TextField>
      </Grid>

      <Grid size={{ xs: 12, md: 1 }}>
        <IconButton
          type="button"
          color="error"
          aria-label={
            `Remove parameter ${index + 1}`
          }
          disabled={disabled}
          onClick={() => {
            onRemove(index);
          }}
        >
          <DeleteIcon />
        </IconButton>
      </Grid>

      <Grid size={{ xs: 12 }}>
        <TextField
          fullWidth
          label="Parameter notes"
          value={row.notes}
          disabled={disabled}
          onChange={(event) => {
            onChange(
              index,
              'notes',
              event.target.value
            );
          }}
        />
      </Grid>
    </Grid>
  </Paper>
);

export default ResultParameterRow;
