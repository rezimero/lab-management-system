import { Autocomplete, TextField } from '@mui/material';
import type {
  OrderPatientOption,
} from '../types/laboratoryOrderCreation.types';

interface Props {
  options: OrderPatientOption[];
  value: OrderPatientOption | null;
  loading: boolean;
  search: string;
  onSearch: (value: string) => void;
  onChange: (value: OrderPatientOption | null) => void;
}

const patientLabel = (patient: OrderPatientOption): string => (
  `${patient.full_name ?? `${patient.first_name} ${patient.last_name}`} — ${patient.medical_record_number}`
);

const OrderPatientSelector = ({
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
    inputValue={search}
    loading={loading}
    filterOptions={(items) => items}
    getOptionLabel={patientLabel}
    isOptionEqualToValue={(option, selected) => option.id === selected.id}
    onInputChange={(_, next, reason) => {
      if (reason !== 'reset') onSearch(next);
    }}
    onChange={(_, next) => {
      onChange(next);
      onSearch('');
    }}
    noOptionsText="No active patients found"
    renderInput={(params) => (
      <TextField
        {...params}
        label="Patient"
        placeholder="Search by name or MRN"
      />
    )}
  />
);

export default OrderPatientSelector;
