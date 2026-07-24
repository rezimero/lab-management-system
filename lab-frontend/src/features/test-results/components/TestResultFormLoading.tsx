import {
  Box,
  CircularProgress,
} from '@mui/material';

const TestResultFormLoading = () => (
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

export default TestResultFormLoading;
