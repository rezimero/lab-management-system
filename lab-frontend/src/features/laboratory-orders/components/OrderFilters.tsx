import type { FormEvent } from 'react';
import {
  Box,
  Button,
  MenuItem,
  TextField,
} from '@mui/material';
import SearchRounded from '@mui/icons-material/SearchRounded';

interface Props {
  query: string;
  status: string;
  priority: string;
  loading: boolean;
  onQueryChange: (value: string) => void;
  onStatusChange: (value: string) => void;
  onPriorityChange: (value: string) => void;
  onApply: () => void;
  onClear: () => void;
}

const OrderFilters = ({
  query,
  status,
  priority,
  loading,
  onQueryChange,
  onStatusChange,
  onPriorityChange,
  onApply,
  onClear,
}: Props) => {
  const submit = (event: FormEvent) => {
    event.preventDefault();
    onApply();
  };

  return (
    <Box
      component="form"
      onSubmit={submit}
      sx={{
        mb: 2.5,
        display: 'grid',
        gridTemplateColumns: {
          xs: '1fr',
          md: 'minmax(260px, 1fr) 180px 160px auto auto',
        },
        gap: 1.25,
      }}
    >
      <TextField
        size="small"
        label="Search orders"
        placeholder="Order number, patient or MRN"
        value={query}
        onChange={(event) => onQueryChange(event.target.value)}
      />
      <TextField
        select
        size="small"
        label="Status"
        value={status}
        onChange={(event) => onStatusChange(event.target.value)}
      >
        <MenuItem value="">All statuses</MenuItem>
        <MenuItem value="pending">Pending</MenuItem>
        <MenuItem value="sample_collected">Sample collected</MenuItem>
        <MenuItem value="in_progress">In progress</MenuItem>
        <MenuItem value="completed">Completed</MenuItem>
        <MenuItem value="cancelled">Cancelled</MenuItem>
      </TextField>
      <TextField
        select
        size="small"
        label="Priority"
        value={priority}
        onChange={(event) => onPriorityChange(event.target.value)}
      >
        <MenuItem value="">All priorities</MenuItem>
        <MenuItem value="routine">Routine</MenuItem>
        <MenuItem value="urgent">Urgent</MenuItem>
        <MenuItem value="stat">Stat</MenuItem>
      </TextField>
      <Button
        type="submit"
        variant="contained"
        startIcon={<SearchRounded />}
        disabled={loading}
      >
        Apply
      </Button>
      <Button
        type="button"
        variant="text"
        onClick={onClear}
        disabled={loading}
      >
        Clear
      </Button>
    </Box>
  );
};

export default OrderFilters;
