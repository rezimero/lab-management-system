import { Box, Typography } from '@mui/material';

const OrderListHeader = () => (
  <Box sx={{ mb: 2.5 }}>
    <Typography variant="h5" sx={{ fontWeight: 760 }}>
      Laboratory orders
    </Typography>
    <Typography variant="body2" color="text.secondary">
      Follow every order from request through approved results and reporting.
    </Typography>
  </Box>
);

export default OrderListHeader;
