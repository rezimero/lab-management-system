import AddRounded from '@mui/icons-material/AddRounded';
import { Box, Button, Typography } from '@mui/material';

interface Props {
  canCreate: boolean;
  onCreate: () => void;
}

const OrderListHeader = ({ canCreate, onCreate }: Props) => (
  <Box
    sx={{
      display: 'flex',
      justifyContent: 'space-between',
      alignItems: 'center',
      gap: 2,
      mb: 2.5,
      flexWrap: 'wrap',
    }}
  >
    <Box>
      <Typography variant="h5" sx={{ fontWeight: 760 }}>
        Laboratory orders
      </Typography>
      <Typography variant="body2" color="text.secondary">
        Follow every order from request through approved results and reporting.
      </Typography>
    </Box>
    {canCreate && (
      <Button
        variant="contained"
        startIcon={<AddRounded />}
        onClick={onCreate}
      >
        Create order
      </Button>
    )}
  </Box>
);

export default OrderListHeader;
