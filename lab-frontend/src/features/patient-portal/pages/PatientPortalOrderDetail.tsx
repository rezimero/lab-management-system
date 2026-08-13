import ArrowBackRounded from
  '@mui/icons-material/ArrowBackRounded';
import AssessmentRounded from
  '@mui/icons-material/AssessmentRounded';
import AssignmentRounded from
  '@mui/icons-material/AssignmentRounded';
import BiotechRounded from
  '@mui/icons-material/BiotechRounded';

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
  useParams,
} from 'react-router-dom';

import {
  fetchPatientPortalOrder,
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
  formatPortalLabel,
  patientPortalErrorMessage,
} from '../utils/patientPortalDisplay';

const PatientPortalOrderDetail = () => {
  const auth = usePatientPortalAuth();

  const {
    orderUuid = '',
  } = useParams<{
    orderUuid: string;
  }>();

  const sessionKey =
    auth.session?.account.uuid ?? 'unknown';

  const query = useQuery({
    queryKey: [
      'patient-portal',
      sessionKey,
      'order',
      orderUuid,
    ],
    queryFn: () => (
      fetchPatientPortalOrder(orderUuid)
    ),
    enabled: Boolean(orderUuid),
  });

  if (query.isLoading) {
    return <PatientPortalLoadingState rows={3} />;
  }

  if (query.error || !query.data) {
    return (
      <Box>
        <Button
          component={Link}
          to="/patient-portal/orders"
          startIcon={<ArrowBackRounded />}
          sx={{ mb: 2 }}
        >
          Back to orders
        </Button>

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
            'We could not load this laboratory order.'
          )}
        </Alert>
      </Box>
    );
  }

  const order = query.data;

  return (
    <Box>
      <Button
        component={Link}
        to="/patient-portal/orders"
        startIcon={<ArrowBackRounded />}
        sx={{ mb: 2 }}
      >
        Back to orders
      </Button>

      <PatientPortalPageHeader
        eyebrow="Order detail"
        title={order.order_number}
        description="Review the current status of this laboratory order and the tests included in it."
        action={
          <PatientPortalStatusChip
            value={order.status}
          />
        }
      />

      <Box
        sx={{
          display: 'grid',
          gridTemplateColumns: {
            xs: '1fr',
            lg: 'minmax(0, 1.3fr) minmax(300px, 0.7fr)',
          },
          gap: 2,
        }}
      >
        <Stack spacing={2}>
          <Card>
            <CardContent>
              <Stack
                direction="row"
                spacing={1.25}
                sx={{
                  alignItems: 'center',
                  mb: 2,
                }}
              >
                <AssignmentRounded
                  sx={{ color: 'primary.main' }}
                />

                <Typography
                  variant="h6"
                  sx={{ fontWeight: 720 }}
                >
                  Order summary
                </Typography>
              </Stack>

              <Divider sx={{ mb: 2 }} />

              <Box
                sx={{
                  display: 'grid',
                  gridTemplateColumns: {
                    xs: '1fr',
                    sm: 'repeat(2, minmax(0, 1fr))',
                  },
                  gap: 2,
                }}
              >
                {[
                  [
                    'Priority',
                    formatPortalLabel(order.priority),
                  ],
                  [
                    'Ordered',
                    formatPortalDate(order.ordered_at),
                  ],
                  [
                    'Sample collected',
                    formatPortalDate(
                      order.sample_collected_at
                    ),
                  ],
                  [
                    'Completed',
                    formatPortalDate(
                      order.completed_at
                    ),
                  ],
                  [
                    'Referring clinician',
                    order.referring_clinician
                      || 'Not recorded',
                  ],
                  [
                    'Tests',
                    `${order.items.length}`,
                  ],
                ].map(([label, value]) => (
                  <Box key={label}>
                    <Typography
                      sx={{
                        color: 'text.secondary',
                        fontSize: '0.72rem',
                        fontWeight: 650,
                      }}
                    >
                      {label}
                    </Typography>

                    <Typography
                      sx={{
                        mt: 0.45,
                        fontSize: '0.86rem',
                        fontWeight: 620,
                      }}
                    >
                      {value}
                    </Typography>
                  </Box>
                ))}
              </Box>
            </CardContent>
          </Card>

          <Card>
            <CardContent>
              <Stack
                direction="row"
                spacing={1.25}
                sx={{
                  alignItems: 'center',
                  mb: 2,
                }}
              >
                <BiotechRounded
                  sx={{ color: 'primary.main' }}
                />

                <Typography
                  variant="h6"
                  sx={{ fontWeight: 720 }}
                >
                  Tests
                </Typography>
              </Stack>

              <Divider sx={{ mb: 1 }} />

              <Stack divider={<Divider />}>
                {order.items.map((item) => (
                  <Box
                    key={item.uuid}
                    sx={{
                      py: 1.6,
                      display: 'flex',
                      gap: 2,
                      alignItems: {
                        xs: 'flex-start',
                        sm: 'center',
                      },
                      flexDirection: {
                        xs: 'column',
                        sm: 'row',
                      },
                      justifyContent: 'space-between',
                    }}
                  >
                    <Box>
                      <Typography
                        sx={{
                          fontWeight: 680,
                        }}
                      >
                        {item.test_name}
                      </Typography>

                      <Typography
                        sx={{
                          mt: 0.35,
                          color: 'text.secondary',
                          fontSize: '0.74rem',
                        }}
                      >
                        {item.category
                          || 'General'}
                        {item.turnaround_time_hours
                          ? ` · ${item.turnaround_time_hours}h turnaround`
                          : ''}
                      </Typography>
                    </Box>

                    <PatientPortalStatusChip
                      value={item.status}
                    />
                  </Box>
                ))}
              </Stack>
            </CardContent>
          </Card>
        </Stack>

        <Card
          sx={{
            alignSelf: 'start',
          }}
        >
          <CardContent>
            <Stack
              direction="row"
              spacing={1.25}
              sx={{
                alignItems: 'center',
              }}
            >
              <AssessmentRounded
                sx={{
                  color: order.report
                    ? 'success.main'
                    : 'text.disabled',
                }}
              />

              <Typography
                variant="h6"
                sx={{ fontWeight: 720 }}
              >
                Laboratory report
              </Typography>
            </Stack>

            <Divider sx={{ my: 2 }} />

            {order.report ? (
              <>
                <Stack
                  direction="row"
                  spacing={1}
                  sx={{
                    alignItems: 'center',
                    flexWrap: 'wrap',
                  }}
                >
                  <Typography
                    sx={{ fontWeight: 680 }}
                  >
                    {order.report.report_number}
                  </Typography>

                  <PatientPortalStatusChip
                    value={
                      order.report.lifecycle_status
                    }
                  />
                </Stack>

                <Typography
                  sx={{
                    mt: 1,
                    color: 'text.secondary',
                    fontSize: '0.76rem',
                    lineHeight: 1.6,
                  }}
                >
                  Generated{' '}
                  {formatPortalDate(
                    order.report.generated_at
                  )}
                </Typography>

                <Button
                  component={Link}
                  to={
                    `/patient-portal/reports/${order.report.uuid}`
                  }
                  variant="contained"
                  fullWidth
                  sx={{ mt: 2.5 }}
                >
                  Open report
                </Button>
              </>
            ) : (
              <>
                <Typography
                  sx={{
                    color: 'text.secondary',
                    lineHeight: 1.65,
                  }}
                >
                  A report will appear here after the
                  laboratory has completed review and
                  issued the current report.
                </Typography>

                <PatientPortalStatusChip
                  value="awaiting report"
                />
              </>
            )}
          </CardContent>
        </Card>
      </Box>
    </Box>
  );
};

export default PatientPortalOrderDetail;
