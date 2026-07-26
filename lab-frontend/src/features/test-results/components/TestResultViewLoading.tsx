import {
  Box,
  CircularProgress,
} from '@mui/material';

const TestResultViewLoading = () => (
  <Box
    sx={{
      display: 'flex',
      justifyContent: 'center',
      py: 8,
    }}
  >
    <CircularProgress />
  </Box>
);

export default TestResultViewLoading;
