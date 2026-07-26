import {
  Box,
  FormControl,
  InputAdornment,
  InputLabel,
  MenuItem,
  Select,
  TextField,
  Typography,
  alpha,
  useTheme,
} from '@mui/material';

import { Search as SearchIcon } from '@mui/icons-material';

import type { TestResultStatus } from '../../../types';

interface TestResultsFiltersProps {
  search: string;
  statusFilter: '' | TestResultStatus;
  total: number;
  onSearchChange: (value: string) => void;
  onStatusChange: (value: '' | TestResultStatus) => void;
}

export const TestResultsFilters = ({
  search,
  statusFilter,
  total,
  onSearchChange,
  onStatusChange,
}: TestResultsFiltersProps) => {
  const theme = useTheme();

  return (
    <Box
      sx={{
        display: 'flex',
        justifyContent: 'space-between',
        alignItems: 'center',
        mb: 3,
        flexWrap: 'wrap',
        gap: 2,
      }}
    >
      <Box sx={{ display: 'flex', gap: 2, flexWrap: 'wrap' }}>
        <TextField
          placeholder="Search results..."
          size="small"
          value={search}
          onChange={(event) => onSearchChange(event.target.value)}
          slotProps={{
            input: {
              startAdornment: (
                <InputAdornment position="start">
                  <SearchIcon sx={{ color: 'text.secondary' }} />
                </InputAdornment>
              ),
              sx: {
                borderRadius: 3,
                backgroundColor: alpha(
                  theme.palette.background.default,
                  0.6
                ),
                '&:hover': {
                  backgroundColor: alpha(
                    theme.palette.background.default,
                    0.8
                  ),
                },
              },
            },
          }}
          sx={{ width: 250 }}
        />

        <FormControl size="small" sx={{ minWidth: 150 }}>
          <InputLabel id="test-result-status-label">
            Status
          </InputLabel>
          <Select
            id="test-result-status"
            labelId="test-result-status-label"
            value={statusFilter}
            onChange={(event) => {
              onStatusChange(
                event.target.value as '' | TestResultStatus
              );
            }}
            label="Status"
            sx={{ borderRadius: 3 }}
          >
            <MenuItem value="">All</MenuItem>
            <MenuItem value="draft">Draft</MenuItem>
            <MenuItem value="submitted">Submitted</MenuItem>
            <MenuItem value="approved">Approved</MenuItem>
            <MenuItem value="rejected">Rejected</MenuItem>
          </Select>
        </FormControl>
      </Box>

      <Typography variant="body2" color="text.secondary">
        {total} results total
      </Typography>
    </Box>
  );
};