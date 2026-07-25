import ArrowBackRounded from '@mui/icons-material/ArrowBackRounded';
import { Box, IconButton, Typography } from '@mui/material';

interface Props {
  onBack: () => void;
}

const OrderCreationHeader = ({ onBack }: Props) => (
  <Box
    sx={{
      display: 'flex',
      alignItems: 'center',
      gap: 1.5,
      mb: 2.5,
    }}
  >
    <IconButton
      aria-label="Back to laboratory orders"
      onClick={onBack}
    >
      <ArrowBackRounded />
    </IconButton>
    <Box>
      <Typography variant="h5" sx={{ fontWeight: 760 }}>
        Create laboratory order
      </Typography>
      <Typography variant="body2" color="text.secondary">
        Select an active patient and one or more catalogue tests.
      </Typography>
    </Box>
  </Box>
);

export default OrderCreationHeader;
