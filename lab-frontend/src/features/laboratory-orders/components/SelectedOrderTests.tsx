import { Box, Button, Divider, Paper, Stack, Typography } from '@mui/material';
import type {
  OrderLabTestOption,
} from '../types/laboratoryOrderCreation.types';

interface Props {
  tests: OrderLabTestOption[];
  onRemove: (id: number) => void;
}

const amount = (value: string | number): number => {
  const parsed = Number(value);
  return Number.isFinite(parsed) ? parsed : 0;
};

const formatNaira = (value: number): string => (
  `₦${new Intl.NumberFormat('en-NG', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(value)}`
);

const SelectedOrderTests = ({ tests, onRemove }: Props) => {
  const total = tests.reduce((sum, test) => sum + amount(test.price), 0);
  return (
    <Paper variant="outlined" sx={{ p: 2 }}>
      <Typography variant="subtitle1" sx={{ fontWeight: 700, mb: 1 }}>
        Selected tests
      </Typography>
      {tests.length === 0 ? (
        <Typography variant="body2" color="text.secondary">
          No laboratory tests selected yet.
        </Typography>
      ) : (
        <Stack spacing={1.25}>
          {tests.map((test) => (
            <Box
              key={test.id}
              sx={{ display: 'flex', alignItems: 'center', gap: 1 }}
            >
              <Box sx={{ flex: 1 }}>
                <Typography variant="body2" sx={{ fontWeight: 650 }}>
                  {test.name}
                </Typography>
                <Typography variant="caption" color="text.secondary">
                  {test.category} · {formatNaira(amount(test.price))}
                </Typography>
              </Box>
              <Button size="small" onClick={() => onRemove(test.id)}>
                Remove
              </Button>
            </Box>
          ))}
          <Divider />
          <Box sx={{ display: 'flex', justifyContent: 'space-between' }}>
            <Typography variant="body2" sx={{ fontWeight: 700 }}>
              Estimated total
            </Typography>
            <Typography variant="body2" sx={{ fontWeight: 750 }}>
              {formatNaira(total)}
            </Typography>
          </Box>
        </Stack>
      )}
    </Paper>
  );
};

export default SelectedOrderTests;
