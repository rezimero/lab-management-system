import {
  Autocomplete,
  Box,
  TextField,
  Typography,
} from '@mui/material';

import type {
  OrderReferringClinicianOption,
} from '../types/laboratoryOrderCreation.types';

interface Props {
  options:
    OrderReferringClinicianOption[];
  value:
    OrderReferringClinicianOption | null;
  loading: boolean;
  search: string;
  onSearch: (value: string) => void;
  onChange: (
    value:
      OrderReferringClinicianOption
      | null
  ) => void;
}

const OrderReferringClinicianSelector = ({
  options,
  value,
  loading,
  search,
  onSearch,
  onChange,
}: Props) => (
  <Autocomplete
    options={options}
    value={value}
    loading={loading}
    inputValue={search}
    onInputChange={(
      _,
      nextValue,
      reason
    ) => {
      if (reason !== 'reset') {
        onSearch(nextValue);
      }
    }}
    onChange={(_, nextValue) => {
      onChange(nextValue);
      onSearch(
        nextValue?.name ?? ''
      );
    }}
    isOptionEqualToValue={(
      option,
      selected
    ) => (
      option.uuid
      === selected.uuid
    )}
    getOptionLabel={(option) => (
      option.name
    )}
    noOptionsText={
      'No active referring clinicians found'
    }
    renderOption={(
      props,
      option
    ) => (
      <Box
        component="li"
        {...props}
        key={option.uuid}
        sx={{
          display: 'flex',
          flexDirection: 'column',
          alignItems:
            'flex-start !important',
        }}
      >
        <Typography
          variant="body2"
          sx={{ fontWeight: 650 }}
        >
          {option.name}
        </Typography>

        <Typography
          variant="caption"
          color="text.secondary"
        >
          {[
            option.organisation,
            option.registration_number,
            option.specialty,
          ]
            .filter(Boolean)
            .join(' • ')
            || 'No additional directory details'}
        </Typography>
      </Box>
    )}
    renderInput={(params) => (
      <TextField
        {...params}
        label="Referring clinician"
        helperText={
          'Select a tenant-owned clinician for durable portal linkage.'
        }
      />
    )}
  />
);

export default
  OrderReferringClinicianSelector;
