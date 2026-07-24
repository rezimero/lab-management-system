import React, { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import {
  Alert,
  Avatar,
  Box,
  Chip,
  FormControl,
  IconButton,
  InputAdornment,
  InputLabel,
  MenuItem,
  Paper,
  Select,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TablePagination,
  TableRow,
  TextField,
  Typography,
  alpha,
  useTheme,
} from '@mui/material';
import {
  Cancel as RejectedIcon,
  CheckCircle as ApprovedIcon,
  HourglassEmpty as SubmittedIcon,
  Pending as DraftIcon,
  Science as ScienceIcon,
  Search as SearchIcon,
  Visibility as VisibilityIcon,
} from '@mui/icons-material';

import api from '../api/client';
import Layout from '../components/Layout/Layout';
import type {
  PaginatedResource,
  TestResultResource,
  TestResultStatus,
} from '../types';

type StatusChipConfig = {
  label: string;
  color: 'success' | 'warning' | 'info' | 'error';
  icon: React.ReactElement;
};

const statusChipConfig: Record<TestResultStatus, StatusChipConfig> = {
  draft: {
    label: 'Draft',
    color: 'warning',
    icon: <DraftIcon sx={{ fontSize: 14 }} />,
  },
  submitted: {
    label: 'Submitted',
    color: 'info',
    icon: <SubmittedIcon sx={{ fontSize: 14 }} />,
  },
  approved: {
    label: 'Approved',
    color: 'success',
    icon: <ApprovedIcon sx={{ fontSize: 14 }} />,
  },
  rejected: {
    label: 'Rejected',
    color: 'error',
    icon: <RejectedIcon sx={{ fontSize: 14 }} />,
  },
};

const getErrorMessage = (error: unknown): string => {
  const responseMessage = (
    error as {
      response?: {
        data?: {
          message?: unknown;
        };
      };
    } | null
  )?.response?.data?.message;

  return typeof responseMessage === 'string'
    ? responseMessage
    : 'Failed to load test results.';
};

const formatDate = (value: string | null): string => {
  if (!value) {
    return '-';
  }

  return new Date(value).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
};

const TestResults: React.FC = () => {
  const navigate = useNavigate();
  const theme = useTheme();
  const [results, setResults] = useState<TestResultResource[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [page, setPage] = useState(0);
  const [rowsPerPage, setRowsPerPage] = useState(10);
  const [total, setTotal] = useState(0);
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState<
    '' | TestResultStatus
  >('');

  useEffect(() => {
    const fetchResults = async () => {
      setLoading(true);
      setError(null);

      try {
        const response = await api.get<
          PaginatedResource<TestResultResource>
        >('/test-results', {
          params: {
            page: page + 1,
            per_page: rowsPerPage,
            search,
            status: statusFilter || undefined,
          },
        });

        setResults(response.data.data ?? []);
        setTotal(response.data.meta?.total ?? 0);
      } catch (requestError: unknown) {
        console.error(
          'Error fetching test results:',
          requestError
        );
        setError(getErrorMessage(requestError));
        setResults([]);
        setTotal(0);
      } finally {
        setLoading(false);
      }
    };

    void fetchResults();
  }, [page, rowsPerPage, search, statusFilter]);

  const getStatusChip = (status: TestResultStatus) => {
    const config = statusChipConfig[status];

    return (
      <Chip
        icon={config.icon}
        label={config.label}
        color={config.color}
        size="small"
        sx={{
          fontWeight: 600,
          '& .MuiChip-label': {
            px: 1.5,
          },
          ...(status === 'approved' && {
            background: `linear-gradient(135deg, ${alpha(
              theme.palette.success.main,
              0.15
            )}, ${alpha(
              theme.palette.success.light,
              0.05
            )})`,
            border: `1px solid ${alpha(
              theme.palette.success.main,
              0.3
            )}`,
          }),
        }}
      />
    );
  };

  const getInitials = (
    firstName?: string,
    lastName?: string
  ) => {
    if (!firstName) {
      return '??';
    }

    return `${firstName[0]}${lastName?.[0] || ''}`.toUpperCase();
  };

  return (
    <Layout title="Test Results">
      <Box sx={{ mb: 4 }}>
        <Typography
          variant="h5"
          sx={{
            fontWeight: 700,
            color: 'text.primary',
          }}
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
          border: `1px solid ${alpha(
            theme.palette.divider,
            0.08
          )}`,
          backdropFilter: 'blur(10px)',
          backgroundColor: alpha(
            theme.palette.background.paper,
            0.6
          ),
        }}
      >
        <Box
          sx={{
            display: 'flex',
            justifyContent: 'space-between',
            alignItems: 'center',
            mb: 3,
            flexWrap: 'wrap',
            gap: 2,
          }}
        >
          <Box sx={{ display: 'flex', gap: 2, flexWrap: 'wrap' }}>
            <TextField
              placeholder="Search results..."
              size="small"
              value={search}
              onChange={(event) => {
                setSearch(event.target.value);
                setPage(0);
              }}
              slotProps={{
                input: {
                  startAdornment: (
                    <InputAdornment position="start">
                      <SearchIcon sx={{ color: 'text.secondary' }} />
                    </InputAdornment>
                  ),
                  sx: {
                    borderRadius: 3,
                    backgroundColor: alpha(
                      theme.palette.background.default,
                      0.6
                    ),
                    '&:hover': {
                      backgroundColor: alpha(
                        theme.palette.background.default,
                        0.8
                      ),
                    },
                  },
                },
              }}
              sx={{ width: 250 }}
            />

            <FormControl size="small" sx={{ minWidth: 150 }}>
              <InputLabel id="test-result-status-label">
                Status
              </InputLabel>
              <Select
                id="test-result-status"
                labelId="test-result-status-label"
                value={statusFilter}
                onChange={(event) => {
                  setStatusFilter(
                    event.target.value as '' | TestResultStatus
                  );
                  setPage(0);
                }}
                label="Status"
                sx={{ borderRadius: 3 }}
              >
                <MenuItem value="">All</MenuItem>
                <MenuItem value="draft">Draft</MenuItem>
                <MenuItem value="submitted">Submitted</MenuItem>
                <MenuItem value="approved">Approved</MenuItem>
                <MenuItem value="rejected">Rejected</MenuItem>
              </Select>
            </FormControl>
          </Box>

          <Typography variant="body2" color="text.secondary">
            {total} results total
          </Typography>
        </Box>

        {error && (
          <Alert severity="error" sx={{ mb: 3 }}>
            {error}
          </Alert>
        )}

        <TableContainer>
          <Table>
            <TableHead>
              <TableRow
                sx={{
                  backgroundColor: alpha(
                    theme.palette.background.default,
                    0.4
                  ),
                }}
              >
                <TableCell sx={{ fontWeight: 700 }}>
                  Patient
                </TableCell>
                <TableCell sx={{ fontWeight: 700 }}>
                  Test
                </TableCell>
                <TableCell sx={{ fontWeight: 700 }}>
                  Order
                </TableCell>
                <TableCell sx={{ fontWeight: 700 }}>
                  Entered
                </TableCell>
                <TableCell sx={{ fontWeight: 700 }}>
                  Status
                </TableCell>
                <TableCell sx={{ fontWeight: 700 }}>
                  Reviewed
                </TableCell>
                <TableCell align="right" sx={{ fontWeight: 700 }}>
                  Actions
                </TableCell>
              </TableRow>
            </TableHead>

            <TableBody>
              {loading ? (
                <TableRow>
                  <TableCell colSpan={7} align="center" sx={{ py: 8 }}>
                    <Typography color="text.secondary">
                      Loading test results...
                    </Typography>
                  </TableCell>
                </TableRow>
              ) : results.length === 0 ? (
                <TableRow>
                  <TableCell colSpan={7} align="center" sx={{ py: 8 }}>
                    <Typography color="text.secondary">
                      No test results found
                    </Typography>
                  </TableCell>
                </TableRow>
              ) : (
                results.map((result) => {
                  const orderItem = result.order_item;
                  const order = orderItem?.order;
                  const patient = order?.patient;
                  const testName = orderItem?.test_name || 'Unknown test';

                  return (
                    <TableRow
                      key={result.uuid}
                      hover
                      sx={{
                        '&:hover': {
                          backgroundColor: alpha(
                            theme.palette.primary.main,
                            0.02
                          ),
                        },
                        transition: 'background-color 0.2s ease',
                      }}
                    >
                      <TableCell>
                        <Box
                          sx={{
                            display: 'flex',
                            alignItems: 'center',
                            gap: 1.5,
                          }}
                        >
                          <Avatar
                            sx={{
                              width: 32,
                              height: 32,
                              bgcolor: theme.palette.info.main,
                              fontSize: '0.75rem',
                              fontWeight: 600,
                            }}
                          >
                            {getInitials(
                              patient?.first_name,
                              patient?.last_name
                            )}
                          </Avatar>
                          <Box>
                            <Typography
                              variant="body2"
                              sx={{ fontWeight: 500 }}
                            >
                              {patient?.full_name || 'Patient unavailable'}
                            </Typography>
                            <Typography
                              variant="caption"
                              color="text.secondary"
                            >
                              {patient?.medical_record_number || 'No MRN'}
                            </Typography>
                          </Box>
                        </Box>
                      </TableCell>

                      <TableCell>
                        <Box
                          sx={{
                            display: 'flex',
                            alignItems: 'center',
                            gap: 1,
                          }}
                        >
                          <ScienceIcon
                            sx={{
                              fontSize: 16,
                              color: 'text.secondary',
                            }}
                          />
                          <Box>
                            <Typography variant="body2">
                              {testName}
                            </Typography>
                            <Typography
                              variant="caption"
                              color="text.secondary"
                            >
                              {orderItem?.category || 'Uncategorised'}
                            </Typography>
                          </Box>
                        </Box>
                      </TableCell>

                      <TableCell>
                        <Typography variant="body2">
                          {order?.order_number || 'Order unavailable'}
                        </Typography>
                        <Typography
                          variant="caption"
                          color="text.secondary"
                        >
                          {order?.priority || '-'}
                        </Typography>
                      </TableCell>

                      <TableCell>
                        {formatDate(result.created_at)}
                      </TableCell>

                      <TableCell>
                        {getStatusChip(result.status)}
                      </TableCell>

                      <TableCell>
                        {formatDate(result.reviewed_at)}
                      </TableCell>

                      <TableCell align="right">
                        <IconButton
                          aria-label={`View ${testName}`}
                          size="small"
                          onClick={() => navigate(
                            `/test-results/${result.uuid}`
                          )}
                          sx={{
                            color: theme.palette.info.main,
                            '&:hover': {
                              backgroundColor: alpha(
                                theme.palette.info.main,
                                0.08
                              ),
                            },
                          }}
                        >
                          <VisibilityIcon fontSize="small" />
                        </IconButton>
                      </TableCell>
                    </TableRow>
                  );
                })
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
          onRowsPerPageChange={(event) => {
            setRowsPerPage(parseInt(event.target.value, 10));
            setPage(0);
          }}
          sx={{
            borderTop: `1px solid ${alpha(
              theme.palette.divider,
              0.06
            )}`,
          }}
        />
      </Paper>
    </Layout>
  );
};

export default TestResults;
