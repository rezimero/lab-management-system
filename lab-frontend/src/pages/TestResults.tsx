import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import {
  Box,
  Paper,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  TablePagination,
  Button,
  IconButton,
  Chip,
  TextField,
  InputAdornment,
  Dialog,
  DialogActions,
  DialogContent,
  DialogContentText,
  DialogTitle,
  Typography,
  Avatar,
  alpha,
  useTheme,
  MenuItem,
  Select,
  FormControl,
  InputLabel,
} from '@mui/material';
import {
  Add as AddIcon,
  Search as SearchIcon,
  Edit as EditIcon,
  Delete as DeleteIcon,
  Visibility as VisibilityIcon,
  Description as DescriptionIcon,
  Person as PersonIcon,
  Science as ScienceIcon,
  CheckCircle as CheckCircleIcon,
  Pending as PendingIcon,
  Cancel as CancelIcon,
  HourglassEmpty as InProgressIcon,
} from '@mui/icons-material';
import Layout from '../components/Layout/Layout';
import api from '../api/client';
import { TestResult } from '../types';

const TestResults: React.FC = () => {
  const navigate = useNavigate();
  const theme = useTheme();
  const [results, setResults] = useState<TestResult[]>([]);
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(0);
  const [rowsPerPage, setRowsPerPage] = useState(10);
  const [total, setTotal] = useState(0);
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);
  const [selectedResult, setSelectedResult] = useState<TestResult | null>(null);

  useEffect(() => {
    fetchResults();
  }, [page, rowsPerPage, search, statusFilter]);

  const fetchResults = async () => {
    setLoading(true);
    try {
      const response = await api.get('/test-results', {
        params: { 
          page: page + 1, 
          per_page: rowsPerPage, 
          search,
          status: statusFilter || undefined,
        },
      });
      setResults(response.data.data);
      setTotal(response.data.total);
    } catch (error) {
      console.error('Error fetching test results:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleDelete = async () => {
    if (!selectedResult) return;
    try {
      await api.delete(`/test-results/${selectedResult.id}`);
      setDeleteDialogOpen(false);
      fetchResults();
    } catch (error) {
      console.error('Error deleting test result:', error);
    }
  };

  const getStatusChip = (status: string) => {
    const config = {
      completed: { 
        label: 'Completed', 
        color: 'success' as const, 
        icon: <CheckCircleIcon sx={{ fontSize: 14 }} /> 
      },
      pending: { 
        label: 'Pending', 
        color: 'warning' as const, 
        icon: <PendingIcon sx={{ fontSize: 14 }} /> 
      },
      in_progress: { 
        label: 'In Progress', 
        color: 'info' as const, 
        icon: <InProgressIcon sx={{ fontSize: 14 }} /> 
      },
      cancelled: { 
        label: 'Cancelled', 
        color: 'error' as const, 
        icon: <CancelIcon sx={{ fontSize: 14 }} /> 
      },
    };

    const configItem = config[status as keyof typeof config] || config.pending;
    return (
      <Chip
        icon={configItem.icon}
        label={configItem.label}
        color={configItem.color}
        size="small"
        sx={{
          fontWeight: 600,
          '& .MuiChip-label': { px: 1.5 },
          ...(status === 'completed' && {
            background: `linear-gradient(135deg, ${alpha(theme.palette.success.main, 0.15)}, ${alpha(theme.palette.success.light, 0.05)})`,
            border: `1px solid ${alpha(theme.palette.success.main, 0.3)}`,
          }),
        }}
      />
    );
  };

  const getInitials = (firstName?: string, lastName?: string) => {
    if (!firstName) return '??';
    return `${firstName[0]}${lastName?.[0] || ''}`.toUpperCase();
  };

  return (
    <Layout title="Test Results">
      <Box sx={{ mb: 4 }}>
        <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', flexWrap: 'wrap', gap: 2 }}>
          <Box>
            <Typography variant="h5" sx={{ fontWeight: 700, color: 'text.primary' }}>
              Test Results
            </Typography>
            <Typography variant="body2" color="text.secondary">
              View and manage all test results
            </Typography>
          </Box>
          <Button
            variant="contained"
            startIcon={<AddIcon />}
            onClick={() => navigate('/test-results/new')}
            sx={{
              background: `linear-gradient(135deg, ${theme.palette.primary.main}, ${theme.palette.primary.dark})`,
              px: 4,
              py: 1.2,
              borderRadius: 3,
              boxShadow: `0 8px 24px ${alpha(theme.palette.primary.main, 0.35)}`,
              '&:hover': {
                boxShadow: `0 12px 32px ${alpha(theme.palette.primary.main, 0.45)}`,
                transform: 'translateY(-2px)',
              },
              transition: 'all 0.3s ease',
              textTransform: 'none',
              fontWeight: 600,
            }}
          >
            Add Test Result
          </Button>
        </Box>
      </Box>

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
        <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 3, flexWrap: 'wrap', gap: 2 }}>
          <Box sx={{ display: 'flex', gap: 2, flexWrap: 'wrap' }}>
            <TextField
              placeholder="Search results..."
              size="small"
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              slotProps={{
                input: {
                  startAdornment: (
                    <InputAdornment position="start">
                      <SearchIcon sx={{ color: 'text.secondary' }} />
                    </InputAdornment>
                  ),
                  sx: {
                    borderRadius: 3,
                    backgroundColor: alpha(theme.palette.background.default, 0.6),
                    '&:hover': {
                      backgroundColor: alpha(theme.palette.background.default, 0.8),
                    },
                  },
                },
              }}
              sx={{ width: 250 }}
            />
            <FormControl size="small" sx={{ minWidth: 150 }}>
              <InputLabel>Status</InputLabel>
              <Select
                value={statusFilter}
                onChange={(e) => setStatusFilter(e.target.value)}
                label="Status"
                sx={{ borderRadius: 3 }}
              >
                <MenuItem value="">All</MenuItem>
                <MenuItem value="completed">Completed</MenuItem>
                <MenuItem value="pending">Pending</MenuItem>
                <MenuItem value="in_progress">In Progress</MenuItem>
                <MenuItem value="cancelled">Cancelled</MenuItem>
              </Select>
            </FormControl>
          </Box>
          <Typography variant="body2" color="text.secondary">
            <strong>{total}</strong> results total
          </Typography>
        </Box>

        <TableContainer>
          <Table>
            <TableHead>
              <TableRow sx={{ backgroundColor: alpha(theme.palette.background.default, 0.4) }}>
                <TableCell sx={{ fontWeight: 700, color: 'text.secondary' }}>Patient</TableCell>
                <TableCell sx={{ fontWeight: 700, color: 'text.secondary' }}>Test</TableCell>
                <TableCell sx={{ fontWeight: 700, color: 'text.secondary' }}>Test Date</TableCell>
                <TableCell sx={{ fontWeight: 700, color: 'text.secondary' }}>Status</TableCell>
                <TableCell sx={{ fontWeight: 700, color: 'text.secondary' }}>Completed</TableCell>
                <TableCell align="right" sx={{ fontWeight: 700, color: 'text.secondary' }}>Actions</TableCell>
              </TableRow>
            </TableHead>
            <TableBody>
              {loading ? (
                <TableRow>
                  <TableCell colSpan={6} align="center" sx={{ py: 8 }}>
                    <Typography color="text.secondary">Loading test results...</Typography>
                  </TableCell>
                </TableRow>
              ) : results.length === 0 ? (
                <TableRow>
                  <TableCell colSpan={6} align="center" sx={{ py: 8 }}>
                    <Typography color="text.secondary">No test results found</Typography>
                  </TableCell>
                </TableRow>
              ) : (
                results.map((result) => (
                  <TableRow
                    key={result.id}
                    hover
                    sx={{
                      '&:hover': {
                        backgroundColor: alpha(theme.palette.primary.main, 0.02),
                      },
                      transition: 'background-color 0.2s ease',
                    }}
                  >
                    <TableCell>
                      <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5 }}>
                        <Avatar
                          sx={{
                            width: 32,
                            height: 32,
                            bgcolor: theme.palette.info.main,
                            fontSize: '0.75rem',
                            fontWeight: 600,
                          }}
                        >
                          {getInitials(result.patient?.first_name, result.patient?.last_name)}
                        </Avatar>
                        <Box>
                          <Typography variant="body2" sx={{ fontWeight: 500 }}>
                            {result.patient ? `${result.patient.first_name} ${result.patient.last_name}` : 'N/A'}
                          </Typography>
                          <Typography variant="caption" color="text.secondary">
                            {result.patient?.medical_record_number || 'No MRN'}
                          </Typography>
                        </Box>
                      </Box>
                    </TableCell>
                    <TableCell>
                      <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                        <ScienceIcon sx={{ fontSize: 16, color: 'text.secondary' }} />
                        <Typography variant="body2">
                          {result.lab_test?.name || 'N/A'}
                        </Typography>
                      </Box>
                    </TableCell>
                    <TableCell>
                      {new Date(result.test_date).toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric',
                      })}
                    </TableCell>
                    <TableCell>{getStatusChip(result.status)}</TableCell>
                    <TableCell>
                      {result.completed_at 
                        ? new Date(result.completed_at).toLocaleDateString('en-US', {
                            year: 'numeric',
                            month: 'short',
                            day: 'numeric',
                          })
                        : '-'
                      }
                    </TableCell>
                    <TableCell align="right">
                      <IconButton
                        size="small"
                        onClick={() => navigate(`/test-results/${result.id}`)}
                        sx={{
                          color: theme.palette.info.main,
                          '&:hover': {
                            backgroundColor: alpha(theme.palette.info.main, 0.08),
                          },
                        }}
                      >
                        <VisibilityIcon fontSize="small" />
                      </IconButton>
                      <IconButton
                        size="small"
                        onClick={() => navigate(`/test-results/${result.id}/edit`)}
                        sx={{
                          color: theme.palette.primary.main,
                          '&:hover': {
                            backgroundColor: alpha(theme.palette.primary.main, 0.08),
                          },
                        }}
                      >
                        <EditIcon fontSize="small" />
                      </IconButton>
                      <IconButton
                        size="small"
                        onClick={() => {
                          setSelectedResult(result);
                          setDeleteDialogOpen(true);
                        }}
                        sx={{
                          color: theme.palette.error.main,
                          '&:hover': {
                            backgroundColor: alpha(theme.palette.error.main, 0.08),
                          },
                        }}
                      >
                        <DeleteIcon fontSize="small" />
                      </IconButton>
                    </TableCell>
                  </TableRow>
                ))
              )}
            </TableBody>
          </Table>
        </TableContainer>
        <TablePagination
          rowsPerPageOptions={[5, 10, 25]}
          component="div"
          count={total}
          rowsPerPage={rowsPerPage}
          page={page}
          onPageChange={(_, newPage) => setPage(newPage)}
          onRowsPerPageChange={(e) => setRowsPerPage(parseInt(e.target.value, 10))}
          sx={{
            borderTop: `1px solid ${alpha(theme.palette.divider, 0.06)}`,
          }}
        />
      </Paper>

      <Dialog
        open={deleteDialogOpen}
        onClose={() => setDeleteDialogOpen(false)}
        maxWidth="sm"
        slotProps={{
          paper: {
            sx: {
              borderRadius: 4,
              p: 1,
            },
          },
        }}
      >
        <DialogTitle sx={{ fontWeight: 700 }}>Confirm Deletion</DialogTitle>
        <DialogContent>
          <DialogContentText>
            Are you sure you want to delete this test result? This action cannot be undone.
          </DialogContentText>
        </DialogContent>
        <DialogActions sx={{ p: 2.5 }}>
          <Button
            onClick={() => setDeleteDialogOpen(false)}
            variant="outlined"
            sx={{ borderRadius: 2, textTransform: 'none' }}
          >
            Cancel
          </Button>
          <Button
            onClick={handleDelete}
            color="error"
            variant="contained"
            sx={{
              borderRadius: 2,
              background: `linear-gradient(135deg, ${theme.palette.error.main}, ${theme.palette.error.dark})`,
              textTransform: 'none',
            }}
          >
            Delete Result
          </Button>
        </DialogActions>
      </Dialog>
    </Layout>
  );
};

export default TestResults;