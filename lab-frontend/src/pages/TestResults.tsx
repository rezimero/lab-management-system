import {
  Alert,
  Box,
  Paper,
  TablePagination,
  Typography,
  alpha,
  useTheme,
} from '@mui/material';

import { useNavigate } from 'react-router-dom';

import Layout from '../components/Layout/Layout';

import { TestResultsFilters } from '../features/test-results/components/TestResultsFilters';
import { TestResultsTable } from '../features/test-results/components/TestResultsTable';
import { useTestResultsList } from '../features/test-results/hooks/useTestResultsList';

const TestResults = () => {
  const navigate = useNavigate();
  const theme = useTheme();

  const {
    results,
    loading,
    error,
    page,
    rowsPerPage,
    total,
    search,
    statusFilter,
    setPage,
    changeSearch,
    changeStatusFilter,
    changeRowsPerPage,
  } = useTestResultsList();

  return (
    <Layout title="Test Results">
      <Box sx={{ mb: 4 }}>
        <Typography
          variant="h5"
          sx={{ fontWeight: 700, color: 'text.primary' }}
        >
          Test Results
        </Typography>
        <Typography variant="body2" color="text.secondary">
          Review test results created through laboratory order items.
        </Typography>
      </Box>

      <Alert severity="info" sx={{ mb: 3, borderRadius: 3 }}>
        New results are entered from a laboratory order item.
        Standalone creation and deletion are not available.
      </Alert>

      <Paper
        elevation={0}
        sx={{
          p: 3,
          borderRadius: 4,
          border: `1px solid ${alpha(theme.palette.divider, 0.08)}`,
          backdropFilter: 'blur(10px)',
          backgroundColor: alpha(theme.palette.background.paper, 0.6),
        }}
      >
        <TestResultsFilters
          search={search}
          statusFilter={statusFilter}
          total={total}
          onSearchChange={changeSearch}
          onStatusChange={changeStatusFilter}
        />

        {error && (
          <Alert severity="error" sx={{ mb: 3 }}>
            {error}
          </Alert>
        )}

        <TestResultsTable
          results={results}
          loading={loading}
          onViewResult={(uuid) => navigate(`/test-results/${uuid}`)}
        />

        <TablePagination
          rowsPerPageOptions={[5, 10, 25]}
          component="div"
          count={total}
          rowsPerPage={rowsPerPage}
          page={page}
          onPageChange={(_, newPage) => setPage(newPage)}
          onRowsPerPageChange={(event) => {
            changeRowsPerPage(Number(event.target.value));
          }}
          sx={{
            borderTop: `1px solid ${alpha(theme.palette.divider, 0.06)}`,
          }}
        />
      </Paper>
    </Layout>
  );
};

export default TestResults;