import {
  useState,
} from 'react';

import SearchRounded from
  '@mui/icons-material/SearchRounded';
import ArrowForwardRounded from
  '@mui/icons-material/ArrowForwardRounded';

import {
  Alert,
  Box,
  Button,
  Card,
  CardContent,
  FormControl,
  InputAdornment,
  InputLabel,
  MenuItem,
  Pagination,
  Select,
  Stack,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  TextField,
  Typography,
} from '@mui/material';

import {
  useQuery,
} from '@tanstack/react-query';

import {
  Link,
} from 'react-router-dom';

import {
  fetchPatientPortalOrders,
} from '../api/patientPortalApi';

import PatientPortalEmptyState from
  '../components/PatientPortalEmptyState';

import PatientPortalLoadingState from
  '../components/PatientPortalLoadingState';

import PatientPortalPageHeader from
  '../components/PatientPortalPageHeader';

import PatientPortalStatusChip from
  '../components/PatientPortalStatusChip';

import {
  usePatientPortalAuth,
} from '../context/PatientPortalAuthContext';

import type {
  PatientPortalOrderStatus,
} from '../types/patientPortal.types';

import {
  formatPortalDate,
  formatPortalLabel,
  patientPortalErrorMessage,
} from '../utils/patientPortalDisplay';

const statusOptions: Array<{
  value: PatientPortalOrderStatus;
  label: string;
}> = [
  { value: 'pending', label: 'Pending' },
  {
    value: 'sample_collected',
    label: 'Sample collected',
  },
  {
    value: 'in_progress',
    label: 'In progress',
  },
  { value: 'completed', label: 'Completed' },
  { value: 'cancelled', label: 'Cancelled' },
];

const PatientPortalOrders = () => {
  const auth = usePatientPortalAuth();

  const [
    page,
    setPage,
  ] = useState(1);

  const [
    search,
    setSearch,
  ] = useState('');

  const [
    status,
    setStatus,
  ] = useState<
    PatientPortalOrderStatus | ''
  >('');

  const sessionKey =
    auth.session?.account.uuid ?? 'unknown';

  const query = useQuery({
    queryKey: [
      'patient-portal',
      sessionKey,
      'orders',
      page,
      search,
      status,
    ],
    queryFn: () => (
      fetchPatientPortalOrders({
        page,
        per_page: 10,
        ...(search.trim()
          ? { search: search.trim() }
          : {}),
        ...(status
          ? { status }
          : {}),
      })
    ),
  });

  const clearFilters = (): void => {
    setSearch('');
    setStatus('');
    setPage(1);
  };

  const hasFilters =
    Boolean(search.trim()) || Boolean(status);

  return (
    <Box>
      <PatientPortalPageHeader
        eyebrow="Laboratory activity"
        title="Orders"
        description="Track the progress of your laboratory requests and see when a current report becomes available."
      />

      <Card sx={{ mb: 2.5 }}>
        <CardContent>
          <Box
            sx={{
              display: 'grid',
              gridTemplateColumns: {
                xs: '1fr',
                md: 'minmax(0, 1fr) 220px auto',
              },
              gap: 1.5,
              alignItems: 'center',
            }}
          >
            <TextField
              fullWidth
              label="Search orders or tests"
              value={search}
              onChange={(event) => {
                setSearch(event.target.value);
                setPage(1);
              }}
              slotProps={{
                input: {
                  startAdornment: (
                    <InputAdornment position="start">
                      <SearchRounded
                        sx={{
                          color: 'text.disabled',
                          fontSize: 20,
                        }}
                      />
                    </InputAdornment>
                  ),
                },
              }}
            />

            <FormControl fullWidth>
              <InputLabel id="patient-order-status-label">
                Status
              </InputLabel>

              <Select
                labelId="patient-order-status-label"
                label="Status"
                value={status}
                onChange={(event) => {
                  setStatus(
                    event.target.value as
                      PatientPortalOrderStatus | ''
                  );
                  setPage(1);
                }}
              >
                <MenuItem value="">
                  All statuses
                </MenuItem>

                {statusOptions.map((option) => (
                  <MenuItem
                    key={option.value}
                    value={option.value}
                  >
                    {option.label}
                  </MenuItem>
                ))}
              </Select>
            </FormControl>

            <Button
              variant="text"
              disabled={!hasFilters}
              onClick={clearFilters}
              sx={{
                justifySelf: {
                  xs: 'stretch',
                  md: 'end',
                },
              }}
            >
              Clear filters
            </Button>
          </Box>
        </CardContent>
      </Card>

      {query.isLoading ? (
        <PatientPortalLoadingState />
      ) : query.error ? (
        <Alert
          severity="error"
          action={
            <Button
              color="inherit"
              size="small"
              onClick={() => {
                void query.refetch();
              }}
            >
              Retry
            </Button>
          }
        >
          {patientPortalErrorMessage(
            query.error,
            'We could not load your laboratory orders.'
          )}
        </Alert>
      ) : (query.data?.data.length ?? 0) === 0 ? (
        <PatientPortalEmptyState
          title={
            hasFilters
              ? 'No orders match these filters'
              : 'No laboratory orders yet'
          }
          description={
            hasFilters
              ? 'Adjust your search or status filter to view other orders.'
              : 'Orders created for your patient record will appear here.'
          }
          {...(
            hasFilters
              ? {
                  actionLabel: 'Clear filters',
                  onAction: clearFilters,
                }
              : {}
          )}
        />
      ) : (
        <>
          <TableContainer
            component={Card}
            sx={{
              display: {
                xs: 'none',
                md: 'block',
              },
            }}
          >
            <Table>
              <TableHead>
                <TableRow>
                  <TableCell>Order</TableCell>
                  <TableCell>Tests</TableCell>
                  <TableCell>Priority</TableCell>
                  <TableCell>Status</TableCell>
                  <TableCell>Ordered</TableCell>
                  <TableCell align="right">
                    Action
                  </TableCell>
                </TableRow>
              </TableHead>

              <TableBody>
                {query.data?.data.map((order) => (
                  <TableRow
                    key={order.uuid}
                    hover
                  >
                    <TableCell>
                      <Typography
                        sx={{
                          fontWeight: 680,
                        }}
                      >
                        {order.order_number}
                      </Typography>

                      <Typography
                        sx={{
                          mt: 0.25,
                          color: 'text.secondary',
                          fontSize: '0.72rem',
                        }}
                      >
                        {order.referring_clinician
                          || 'No referring clinician recorded'}
                      </Typography>
                    </TableCell>

                    <TableCell>
                      <Typography
                        sx={{
                          maxWidth: 320,
                          fontSize: '0.8rem',
                        }}
                      >
                        {order.items
                          .map((item) => item.test_name)
                          .join(', ')}
                      </Typography>
                    </TableCell>

                    <TableCell>
                      {formatPortalLabel(
                        order.priority
                      )}
                    </TableCell>

                    <TableCell>
                      <PatientPortalStatusChip
                        value={order.status}
                      />
                    </TableCell>

                    <TableCell>
                      {formatPortalDate(
                        order.ordered_at
                      )}
                    </TableCell>

                    <TableCell align="right">
                      <Button
                        component={Link}
                        to={
                          `/patient-portal/orders/${order.uuid}`
                        }
                        size="small"
                        endIcon={
                          <ArrowForwardRounded />
                        }
                      >
                        View
                      </Button>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </TableContainer>

          <Stack
            spacing={1.5}
            sx={{
              display: {
                xs: 'flex',
                md: 'none',
              },
            }}
          >
            {query.data?.data.map((order) => (
              <Card key={order.uuid}>
                <CardContent>
                  <Box
                    sx={{
                      display: 'flex',
                      gap: 1,
                      alignItems: 'flex-start',
                      justifyContent: 'space-between',
                    }}
                  >
                    <Box sx={{ minWidth: 0 }}>
                      <Typography
                        sx={{
                          fontWeight: 700,
                        }}
                      >
                        {order.order_number}
                      </Typography>

                      <Typography
                        sx={{
                          mt: 0.35,
                          color: 'text.secondary',
                          fontSize: '0.74rem',
                        }}
                      >
                        {formatPortalDate(
                          order.ordered_at
                        )}
                      </Typography>
                    </Box>

                    <PatientPortalStatusChip
                      value={order.status}
                    />
                  </Box>

                  <Typography
                    sx={{
                      mt: 1.5,
                      color: 'text.secondary',
                      fontSize: '0.76rem',
                    }}
                  >
                    {order.items
                      .map((item) => item.test_name)
                      .join(', ')}
                  </Typography>

                  <Button
                    component={Link}
                    to={
                      `/patient-portal/orders/${order.uuid}`
                    }
                    fullWidth
                    variant="outlined"
                    sx={{ mt: 2 }}
                  >
                    View order
                  </Button>
                </CardContent>
              </Card>
            ))}
          </Stack>

          {(query.data?.meta.last_page ?? 1) > 1 && (
            <Box
              sx={{
                display: 'flex',
                justifyContent: 'center',
                mt: 3,
              }}
            >
              <Pagination
                page={page}
                count={
                  query.data?.meta.last_page ?? 1
                }
                onChange={(_event, value) => {
                  setPage(value);
                  window.scrollTo({
                    top: 0,
                    behavior: 'smooth',
                  });
                }}
                color="primary"
              />
            </Box>
          )}
        </>
      )}
    </Box>
  );
};

export default PatientPortalOrders;
