import ArrowForwardRounded from
  '@mui/icons-material/ArrowForwardRounded';
import AssessmentRounded from
  '@mui/icons-material/AssessmentRounded';
import AssignmentRounded from
  '@mui/icons-material/AssignmentRounded';
import PeopleAltRounded from
  '@mui/icons-material/PeopleAltRounded';

import {
  Alert,
  Box,
  Button,
  Card,
  CardContent,
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
  fetchClinicianPortalOrders,
  fetchClinicianPortalPatients,
  fetchClinicianPortalReports,
} from '../api/clinicianPortalApi';

import ClinicianPortalLoadingState from
  '../components/ClinicianPortalLoadingState';

import ClinicianPortalPageHeader from
  '../components/ClinicianPortalPageHeader';

import ClinicianPortalStatusChip from
  '../components/ClinicianPortalStatusChip';

import {
  useClinicianPortalAuth,
} from '../context/ClinicianPortalAuthContext';

import {
  clinicianDisplayName,
  clinicianPortalErrorMessage,
  formatPortalDate,
} from '../utils/clinicianPortalDisplay';

const ClinicianPortalDashboard = () => {
  const auth = useClinicianPortalAuth();

  const sessionKey =
    auth.session?.account.uuid ?? 'unknown';

  const patientsQuery = useQuery({
    queryKey: [
      'clinician-portal',
      sessionKey,
      'dashboard-patients',
    ],
    queryFn: () => (
      fetchClinicianPortalPatients({
        page: 1,
        per_page: 5,
      })
    ),
  });

  const ordersQuery = useQuery({
    queryKey: [
      'clinician-portal',
      sessionKey,
      'dashboard-orders',
    ],
    queryFn: () => (
      fetchClinicianPortalOrders({
        page: 1,
        per_page: 5,
      })
    ),
  });

  const reportsQuery = useQuery({
    queryKey: [
      'clinician-portal',
      sessionKey,
      'dashboard-reports',
    ],
    queryFn: () => (
      fetchClinicianPortalReports({
        page: 1,
        per_page: 5,
      })
    ),
  });

  const displayName = clinicianDisplayName(
    auth.session?.clinician.name
  );

  const latestReport =
    reportsQuery.data?.data[0];

  const error =
    patientsQuery.error
    ?? ordersQuery.error
    ?? reportsQuery.error;

  const retry = (): void => {
    void patientsQuery.refetch();
    void ordersQuery.refetch();
    void reportsQuery.refetch();
  };

  return (
    <Box>
      <ClinicianPortalPageHeader
        eyebrow="Overview"
        title={`Welcome, ${displayName}.`}
        description="Review patients and laboratory activity explicitly linked to your referring-clinician record."
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
          {clinicianPortalErrorMessage(
            error,
            'We could not load your clinician portal overview.'
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
                <PeopleAltRounded />
              </Box>

              <Box>
                <Typography
                  sx={{
                    color: 'text.secondary',
                    fontSize: '0.76rem',
                    fontWeight: 650,
                  }}
                >
                  Linked patients
                </Typography>

                <Typography
                  variant="h4"
                  sx={{
                    mt: 0.25,
                    fontWeight: 760,
                  }}
                >
                  {patientsQuery.data?.meta.total ?? '—'}
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
                  Referred orders
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
                  Current reports
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
      </Box>

      {(
        patientsQuery.isLoading
        || ordersQuery.isLoading
        || reportsQuery.isLoading
      ) ? (
        <ClinicianPortalLoadingState rows={2} />
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
                    Recent referred orders
                  </Typography>

                  <Typography
                    sx={{
                      mt: 0.35,
                      color: 'text.secondary',
                      fontSize: '0.78rem',
                    }}
                  >
                    Laboratory activity linked to your clinician record.
                  </Typography>
                </Box>

                <Button
                  component={Link}
                  to="/clinician-portal/orders"
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
                        `/clinician-portal/orders/${order.uuid}`
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
                            {order.patient?.full_name
                              || 'Patient'}
                          </Typography>

                          <Typography
                            noWrap
                            sx={{
                              mt: 0.25,
                              color: 'text.secondary',
                              fontSize: '0.72rem',
                            }}
                          >
                            {order.order_number}
                            {' · '}
                            {order.items
                              .map((item) => item.test_name)
                              .join(', ')
                              || 'Laboratory tests'}
                          </Typography>
                        </Box>

                        <ClinicianPortalStatusChip
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
                    No explicitly linked laboratory orders are available yet.
                  </Typography>
                )}
              </Stack>
            </CardContent>
          </Card>

          <Stack spacing={2}>
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
                      Referred patients
                    </Typography>

                    <Typography
                      sx={{
                        mt: 0.35,
                        color: 'text.secondary',
                        fontSize: '0.78rem',
                      }}
                    >
                      Patients derived from linked orders.
                    </Typography>
                  </Box>

                  <Button
                    component={Link}
                    to="/clinician-portal/patients"
                    size="small"
                  >
                    View all
                  </Button>
                </Box>

                <Divider sx={{ my: 2 }} />

                <Stack spacing={1}>
                  {(patientsQuery.data?.data ?? [])
                    .slice(0, 3)
                    .map((patient) => (
                      <Box
                        key={
                          patient.medical_record_number
                          || patient.full_name
                        }
                        sx={{
                          px: 0.5,
                          py: 0.75,
                        }}
                      >
                        <Typography
                          sx={{
                            fontSize: '0.82rem',
                            fontWeight: 680,
                          }}
                        >
                          {patient.full_name
                            || 'Patient'}
                        </Typography>

                        <Typography
                          sx={{
                            mt: 0.2,
                            color: 'text.secondary',
                            fontSize: '0.72rem',
                          }}
                        >
                          {patient.medical_record_number
                            || 'MRN not recorded'}
                        </Typography>
                      </Box>
                    ))}

                  {(patientsQuery.data?.data.length ?? 0) === 0 && (
                    <Typography
                      sx={{
                        py: 2,
                        color: 'text.secondary',
                        textAlign: 'center',
                      }}
                    >
                      No linked patients yet.
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
                  Most recent current issued or amended report.
                </Typography>

                <Divider sx={{ my: 2 }} />

                {latestReport ? (
                  <Box>
                    <Typography
                      sx={{ fontWeight: 700 }}
                    >
                      {latestReport.patient.full_name
                        || 'Patient'}
                    </Typography>

                    <Typography
                      sx={{
                        mt: 0.35,
                        color: 'text.secondary',
                        fontSize: '0.74rem',
                      }}
                    >
                      {latestReport.report_number}
                    </Typography>

                    <Box sx={{ mt: 1.25 }}>
                      <ClinicianPortalStatusChip
                        value={
                          latestReport.lifecycle_status
                        }
                      />
                    </Box>

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
                        `/clinician-portal/reports/${latestReport.uuid}`
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
                    No current report is available yet.
                  </Typography>
                )}
              </CardContent>
            </Card>
          </Stack>
        </Box>
      )}
    </Box>
  );
};

export default ClinicianPortalDashboard;
