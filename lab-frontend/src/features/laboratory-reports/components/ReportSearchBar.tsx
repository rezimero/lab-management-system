import type {
  FormEvent,
} from 'react';

import {
  Box,
  Button,
  TextField,
} from '@mui/material';

import SearchRounded from
  '@mui/icons-material/SearchRounded';

interface Props {
  query: string;
  loading: boolean;
  onQueryChange: (
    value: string
  ) => void;
  onSearch: () => void;
}

const ReportSearchBar = ({
  query,
  loading,
  onQueryChange,
  onSearch,
}: Props) => {
  const handleSubmit = (
    event: FormEvent
  ) => {
    event.preventDefault();
    onSearch();
  };

  return (
    <Box
      component="form"
      onSubmit={handleSubmit}
      sx={{
        mb: 2.5,
        display: 'flex',
        flexDirection: {
          xs: 'column',
          sm: 'row',
        },
        gap: 1.25,
      }}
    >
      <TextField
        fullWidth
        size="small"
        label="Search reports"
        placeholder="Report number, order, patient or MRN"
        value={query}
        onChange={(event) => {
          onQueryChange(
            event.target.value
          );
        }}
      />

      <Button
        type="submit"
        variant="outlined"
        startIcon={<SearchRounded />}
        disabled={loading}
        sx={{
          minWidth: 120,
        }}
      >
        Search
      </Button>
    </Box>
  );
};

export default ReportSearchBar;
