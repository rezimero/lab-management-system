import {
  useState,
} from 'react';

import ArrowForwardRounded from
  '@mui/icons-material/ArrowForwardRounded';
import SearchRounded from
  '@mui/icons-material/SearchRounded';

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
  fetchClinicianPortalOrders,
} from '../api/clinicianPortalApi';

import ClinicianPortalEmptyState from
  '../components/ClinicianPortalEmptyState';

import ClinicianPortalLoadingState from
  '../components/ClinicianPortalLoadingState';

import ClinicianPortalPageHeader from
  '../components/ClinicianPortalPageHeader';

import ClinicianPortalStatusChip from
  '../components/ClinicianPortalStatusChip';

import {
  useClinicianPortalAuth,
} from '../context/ClinicianPortalAuthContext';

import type {
  ClinicianPortalOrderStatus,
} from '../types/clinicianPortal.types';

import {
  clinicianPortalErrorMessage,
  formatPortalDate,
  formatPortalLabel,
} from '../utils/clinicianPortalDisplay';

const statusOptions: Array<{
  value: ClinicianPortalOrderStatus;
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

const ClinicianPortalOrders = () => {
  const auth = useClinicianPortalAuth();

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
    ClinicianPortalOrderStatus | ''
  >('');

  const sessionKey =
    auth.session?.account.uuid ?? 'unknown';

  const query = useQuery({
    queryKey: [
      'clinician-portal',
      sessionKey,
      'orders',
      page,
      search,
      status,
    ],
    queryFn: () => (
      fetchClinicianPortalOrders({
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
      <ClinicianPortalPageHeader
        eyebrow="Referral activity"
        title="Orders"
        description="Track laboratory orders explicitly linked to your clinician record and see when current reports become available."
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
              label="Search order, patient or test"
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
              <InputLabel id="clinician-order-status-label">
                Status
              </InputLabel>

              <Select
                labelId="clinician-order-status-label"
                label="Status"
                value={status}
                onChange={(event) => {
                  setStatus(
                    event.target.value as
                      ClinicianPortalOrderStatus | ''
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
        <ClinicianPortalLoadingState />
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
          {clinicianPortalErrorMessage(
            query.error,
            'We could not load your linked laboratory orders.'
          )}
        </Alert>
      ) : (query.data?.data.length ?? 0) === 0 ? (
        <ClinicianPortalEmptyState
          title={
            hasFilters
              ? 'No orders match these filters'
              : 'No linked laboratory orders yet'
          }
          description={
            hasFilters
              ? 'Adjust your search or status filter to view other orders.'
              : 'Only orders explicitly linked to your clinician directory record will appear here.'
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
                  <TableCell>Patient</TableCell>
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
                        sx={{ fontWeight: 680 }}
                      >
                        {order.order_number}
                      </Typography>
                    </TableCell>

                    <TableCell>
                      <Typography
                        sx={{ fontWeight: 620 }}
                      >
                        {order.patient?.full_name
                          || [
                              order.patient?.first_name,
                              order.patient?.last_name,
                            ]
                              .filter(Boolean)
                              .join(' ')
                          || 'Patient'}
                      </Typography>

                      <Typography
                        sx={{
                          mt: 0.25,
                          color: 'text.secondary',
                          fontSize: '0.72rem',
                        }}
                      >
                        {order.patient?.medical_record_number
                          || 'MRN not recorded'}
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
                      <ClinicianPortalStatusChip
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
                          `/clinician-portal/orders/${order.uuid}`
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
                        sx={{ fontWeight: 700 }}
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
                        {order.patient?.full_name
                          || 'Patient'}
                      </Typography>
                    </Box>

                    <ClinicianPortalStatusChip
                      value={order.status}
                    />
                  </Box>

                  <Typography
                    sx={{
                      mt: 1.4,
                      color: 'text.secondary',
                      fontSize: '0.76rem',
                    }}
                  >
                    {order.items
                      .map((item) => item.test_name)
                      .join(', ')}
                  </Typography>

                  <Typography
                    sx={{
                      mt: 0.75,
                      color: 'text.secondary',
                      fontSize: '0.74rem',
                    }}
                  >
                    Ordered{' '}
                    {formatPortalDate(
                      order.ordered_at
                    )}
                  </Typography>

                  <Button
                    component={Link}
                    to={
                      `/clinician-portal/orders/${order.uuid}`
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

export default ClinicianPortalOrders;
