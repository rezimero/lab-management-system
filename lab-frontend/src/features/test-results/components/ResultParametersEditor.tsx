import {
  Alert,
  Box,
  Button,
  Typography,
} from '@mui/material';

import {
  Add as AddIcon,
} from '@mui/icons-material';

import type {
  ResultFormRow,
} from '../types/testResultForm.types';

import ResultParameterRow from './ResultParameterRow';

interface Props {
  rows: ResultFormRow[];
  disabled: boolean;
  onAdd: () => void;
  onRemove: (
    index: number
  ) => void;
  onChange: (
    index: number,
    field: keyof ResultFormRow,
    value: string
  ) => void;
}

const ResultParametersEditor = ({
  rows,
  disabled,
  onAdd,
  onRemove,
  onChange,
}: Props) => (
  <>
    <Box
      sx={{
        mb: 2,
        display: 'flex',
        flexDirection: {
          xs: 'column',
          sm: 'row',
        },
        justifyContent: 'space-between',
        alignItems: {
          xs: 'stretch',
          sm: 'center',
        },
        gap: 1.5,
      }}
    >
      <Box>
        <Typography
          variant="h6"
          sx={{
            fontWeight: 750,
          }}
        >
          Result parameters
        </Typography>

        <Typography
          variant="body2"
          color="text.secondary"
        >
          Record values, ranges, flags
          and parameter notes.
        </Typography>
      </Box>

      <Button
        type="button"
        variant="outlined"
        startIcon={<AddIcon />}
        disabled={disabled}
        onClick={onAdd}
      >
        Add parameter
      </Button>
    </Box>

    {rows.length === 0 ? (
      <Alert
        severity="info"
        sx={{
          mb: 2,
        }}
      >
        No result parameters have
        been added.
      </Alert>
    ) : (
      rows.map((row, index) => (
        <ResultParameterRow
          key={index}
          index={index}
          row={row}
          disabled={disabled}
          onChange={onChange}
          onRemove={onRemove}
        />
      ))
    )}
  </>
);

export default ResultParametersEditor;
