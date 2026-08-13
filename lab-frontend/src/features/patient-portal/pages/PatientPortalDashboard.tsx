import ArrowForwardRounded from
  '@mui/icons-material/ArrowForwardRounded';
import AssessmentRounded from
  '@mui/icons-material/AssessmentRounded';
import AssignmentRounded from
  '@mui/icons-material/AssignmentRounded';
import EventAvailableRounded from
  '@mui/icons-material/EventAvailableRounded';

import {
  Alert,
  Box,
  Button,
  Card,
  CardContent,
  Chip,
  Divider,
  Stack,
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
  fetchPatientPortalReports,
} from '../api/patientPortalApi';

import PatientPortalLoadingState from
  '../components/PatientPortalLoadingState';

import PatientPortalPageHeader from
  '../components/PatientPortalPageHeader';

import PatientPortalStatusChip from
  '../components/PatientPortalStatusChip';

import {
  usePatientPortalAuth,
} from '../context/PatientPortalAuthContext';

import {
  formatPortalDate,
  patientDisplayName,
  patientPortalErrorMessage,
} from '../utils/patientPortalDisplay';

const PatientPortalDashboard = () => {
  const auth = usePatientPortalAuth();

  const sessionKey =
    auth.session?.account.uuid ?? 'unknown';

  const ordersQuery = useQuery({
    queryKey: [
      'patient-portal',
      sessionKey,
      'dashboard-orders',
    ],
    queryFn: () => (
      fetchPatientPortalOrders({
        page: 1,
        per_page: 5,
      })
    ),
  });

  const reportsQuery = useQuery({
    queryKey: [
      'patient-portal',
      sessionKey,
      'dashboard-reports',
    ],
    queryFn: () => (
      fetchPatientPortalReports({
        page: 1,
        per_page: 5,
      })
    ),
  });

  const displayName = patientDisplayName(
    auth.session?.patient.first_name,
    auth.session?.patient.last_name,
    auth.session?.patient.full_name
  );

  const latestOrder =
    ordersQuery.data?.data[0];

  const latestReport =
    reportsQuery.data?.data[0];

  const error =
    ordersQuery.error ?? reportsQuery.error;

  const retry = (): void => {
    void ordersQuery.refetch();
    void reportsQuery.refetch();
  };

  return (
    <Box>
      <PatientPortalPageHeader
        eyebrow="Overview"
        title={`Welcome, ${displayName}.`}
        description="Track your laboratory orders and securely access current issued reports."
      />

      {error && (
        <Alert
          severity="error"
          action={
            <Button
              color="inherit"
              size="small"
              onClick={retry}
            >
              Retry
            </Button>
          }
          sx={{ mb: 3 }}
        >
          {patientPortalErrorMessage(
            error,
            'We could not load your portal overview.'
          )}
        </Alert>
      )}

      <Box
        sx={{
          display: 'grid',
          gridTemplateColumns: {
            xs: '1fr',
            sm: 'repeat(2, minmax(0, 1fr))',
            lg: 'repeat(3, minmax(0, 1fr))',
          },
          gap: 2,
          mb: 3,
        }}
      >
        <Card>
          <CardContent>
            <Stack
              direction="row"
              spacing={1.5}
              sx={{ alignItems: 'center' }}
            >
              <Box
                sx={{
                  width: 44,
                  height: 44,
                  display: 'grid',
                  placeItems: 'center',
                  borderRadius: '13px',
                  color: 'primary.main',
                  backgroundColor:
                    'rgba(36,107,253,0.08)',
                }}
              >
                <AssignmentRounded />
              </Box>

              <Box>
                <Typography
                  sx={{
                    color: 'text.secondary',
                    fontSize: '0.76rem',
                    fontWeight: 650,
                  }}
                >
                  Laboratory orders
                </Typography>

                <Typography
                  variant="h4"
                  sx={{
                    mt: 0.25,
                    fontWeight: 760,
                  }}
                >
                  {ordersQuery.data?.meta.total ?? '—'}
                </Typography>
              </Box>
            </Stack>
          </CardContent>
        </Card>

        <Card>
          <CardContent>
            <Stack
              direction="row"
              spacing={1.5}
              sx={{ alignItems: 'center' }}
            >
              <Box
                sx={{
                  width: 44,
                  height: 44,
                  display: 'grid',
                  placeItems: 'center',
                  borderRadius: '13px',
                  color: 'success.main',
                  backgroundColor:
                    'rgba(7,148,85,0.08)',
                }}
              >
                <AssessmentRounded />
              </Box>

              <Box>
                <Typography
                  sx={{
                    color: 'text.secondary',
                    fontSize: '0.76rem',
                    fontWeight: 650,
                  }}
                >
                  Reports available
                </Typography>

                <Typography
                  variant="h4"
                  sx={{
                    mt: 0.25,
                    fontWeight: 760,
                  }}
                >
                  {reportsQuery.data?.meta.total ?? '—'}
                </Typography>
              </Box>
            </Stack>
          </CardContent>
        </Card>

        <Card>
          <CardContent>
            <Stack
              direction="row"
              spacing={1.5}
              sx={{ alignItems: 'center' }}
            >
              <Box
                sx={{
                  width: 44,
                  height: 44,
                  display: 'grid',
                  placeItems: 'center',
                  borderRadius: '13px',
                  color: 'warning.main',
                  backgroundColor:
                    'rgba(247,144,9,0.10)',
                }}
              >
                <EventAvailableRounded />
              </Box>

              <Box>
                <Typography
                  sx={{
                    color: 'text.secondary',
                    fontSize: '0.76rem',
                    fontWeight: 650,
                  }}
                >
                  Latest order
                </Typography>

                <Box sx={{ mt: 0.8 }}>
                  {latestOrder ? (
                    <PatientPortalStatusChip
                      value={latestOrder.status}
                    />
                  ) : (
                    <Chip
                      size="small"
                      label="No orders yet"
                    />
                  )}
                </Box>
              </Box>
            </Stack>
          </CardContent>
        </Card>
      </Box>

      {(
        ordersQuery.isLoading
        || reportsQuery.isLoading
      ) ? (
        <PatientPortalLoadingState rows={2} />
      ) : (
        <Box
          sx={{
            display: 'grid',
            gridTemplateColumns: {
              xs: '1fr',
              lg: 'minmax(0, 1.35fr) minmax(320px, 0.65fr)',
            },
            gap: 2,
          }}
        >
          <Card>
            <CardContent>
              <Box
                sx={{
                  display: 'flex',
                  gap: 2,
                  alignItems: 'center',
                  justifyContent: 'space-between',
                }}
              >
                <Box>
                  <Typography
                    variant="h6"
                    sx={{ fontWeight: 730 }}
                  >
                    Recent orders
                  </Typography>

                  <Typography
                    sx={{
                      mt: 0.35,
                      color: 'text.secondary',
                      fontSize: '0.78rem',
                    }}
                  >
                    Your most recent laboratory requests.
                  </Typography>
                </Box>

                <Button
                  component={Link}
                  to="/patient-portal/orders"
                  size="small"
                  endIcon={<ArrowForwardRounded />}
                >
                  View all
                </Button>
              </Box>

              <Divider sx={{ my: 2 }} />

              <Stack spacing={1}>
                {(ordersQuery.data?.data ?? [])
                  .map((order) => (
                    <Button
                      key={order.uuid}
                      component={Link}
                      to={
                        `/patient-portal/orders/${order.uuid}`
                      }
                      sx={{
                        justifyContent: 'stretch',
                        p: 1.25,
                        borderRadius: '12px',
                        color: 'text.primary',
                        textTransform: 'none',
                        textAlign: 'left',
                      }}
                    >
                      <Box
                        sx={{
                          width: '100%',
                          display: 'flex',
                          gap: 1.5,
                          alignItems: 'center',
                          justifyContent: 'space-between',
                        }}
                      >
                        <Box sx={{ minWidth: 0 }}>
                          <Typography
                            noWrap
                            sx={{
                              fontSize: '0.84rem',
                              fontWeight: 680,
                            }}
                          >
                            {order.order_number}
                          </Typography>

                          <Typography
                            noWrap
                            sx={{
                              mt: 0.25,
                              color: 'text.secondary',
                              fontSize: '0.72rem',
                            }}
                          >
                            {order.items
                              .map((item) => item.test_name)
                              .join(', ')
                              || 'Laboratory tests'}
                          </Typography>
                        </Box>

                        <PatientPortalStatusChip
                          value={order.status}
                        />
                      </Box>
                    </Button>
                  ))}

                {(ordersQuery.data?.data.length ?? 0) === 0 && (
                  <Typography
                    sx={{
                      py: 3,
                      color: 'text.secondary',
                      textAlign: 'center',
                    }}
                  >
                    No laboratory orders are available yet.
                  </Typography>
                )}
              </Stack>
            </CardContent>
          </Card>

          <Card>
            <CardContent>
              <Typography
                variant="h6"
                sx={{ fontWeight: 730 }}
              >
                Latest report
              </Typography>

              <Typography
                sx={{
                  mt: 0.35,
                  color: 'text.secondary',
                  fontSize: '0.78rem',
                }}
              >
                Current issued or amended result report.
              </Typography>

              <Divider sx={{ my: 2 }} />

              {latestReport ? (
                <Box>
                  <Stack
                    direction="row"
                    spacing={1}
                    sx={{
                      alignItems: 'center',
                      flexWrap: 'wrap',
                    }}
                  >
                    <Typography
                      sx={{
                        fontWeight: 700,
                      }}
                    >
                      {latestReport.report_number}
                    </Typography>

                    <PatientPortalStatusChip
                      value={
                        latestReport.lifecycle_status
                      }
                    />
                  </Stack>

                  <Typography
                    sx={{
                      mt: 1,
                      color: 'text.secondary',
                      fontSize: '0.78rem',
                    }}
                  >
                    Generated{' '}
                    {formatPortalDate(
                      latestReport.generated_at
                    )}
                  </Typography>

                  <Button
                    component={Link}
                    to={
                      `/patient-portal/reports/${latestReport.uuid}`
                    }
                    variant="outlined"
                    fullWidth
                    sx={{ mt: 2.5 }}
                  >
                    Open report
                  </Button>
                </Box>
              ) : (
                <Typography
                  sx={{
                    py: 3,
                    color: 'text.secondary',
                    textAlign: 'center',
                  }}
                >
                  No issued report is available yet.
                </Typography>
              )}
            </CardContent>
          </Card>
        </Box>
      )}
    </Box>
  );
};

export default PatientPortalDashboard;
