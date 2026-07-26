import { Autocomplete, TextField } from '@mui/material';
import type {
  OrderLabTestOption,
} from '../types/laboratoryOrderCreation.types';

interface Props {
  options: OrderLabTestOption[];
  value: OrderLabTestOption[];
  loading: boolean;
  search: string;
  onSearch: (value: string) => void;
  onChange: (value: OrderLabTestOption[]) => void;
}

const testLabel = (test: OrderLabTestOption): string => (
  `${test.name} — ${test.category}`
);

const OrderTestSelector = ({
  options,
  value,
  loading,
  search,
  onSearch,
  onChange,
}: Props) => (
  <Autocomplete
    multiple
    options={options}
    value={value}
    inputValue={search}
    loading={loading}
    filterOptions={(items) => items}
    getOptionLabel={testLabel}
    isOptionEqualToValue={(option, selected) => option.id === selected.id}
    onInputChange={(_, next, reason) => {
      if (reason !== 'reset') onSearch(next);
    }}
    onChange={(_, next) => {
      onChange(next);
      onSearch('');
    }}
    noOptionsText="No active laboratory tests found"
    renderInput={(params) => (
      <TextField
        {...params}
        label="Laboratory tests"
        placeholder="Search catalogue tests"
      />
    )}
  />
);

export default OrderTestSelector;
