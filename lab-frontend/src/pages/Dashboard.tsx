import {
  ReactNode,
  useMemo,
} from 'react';

import AddRounded from '@mui/icons-material/AddRounded';
import ArrowForwardRounded from '@mui/icons-material/ArrowForwardRounded';
import BiotechRounded from '@mui/icons-material/BiotechRounded';
import CheckCircleRounded from '@mui/icons-material/CheckCircleRounded';
import DomainRounded from '@mui/icons-material/DomainRounded';
import FactCheckRounded from '@mui/icons-material/FactCheckRounded';
import GroupsRounded from '@mui/icons-material/GroupsRounded';
import HourglassTopRounded from '@mui/icons-material/HourglassTopRounded';
import PeopleAltRounded from '@mui/icons-material/PeopleAltRounded';
import PersonAddAlt1Rounded from '@mui/icons-material/PersonAddAlt1Rounded';
import RefreshRounded from '@mui/icons-material/RefreshRounded';
import ScienceRounded from '@mui/icons-material/ScienceRounded';
import ShieldRounded from '@mui/icons-material/ShieldRounded';
import TaskAltRounded from '@mui/icons-material/TaskAltRounded';
import VerifiedRounded from '@mui/icons-material/VerifiedRounded';

import {
  Alert,
  Box,
  Button,
  Card,
  CardContent,
  Chip,
  CircularProgress,
  LinearProgress,
  Skeleton,
  Typography,
} from '@mui/material';

import {
  alpha,
} from '@mui/material/styles';

import {
  useQuery,
} from '@tanstack/react-query';

import {
  motion,
} from 'framer-motion';

import {
  useNavigate,
} from 'react-router-dom';

import api from '../api/client';
import Layout from '../components/Layout/Layout';
import { useAuth } from '../contexts/AuthContext';

interface PatientStats {
  total_patients: number;
  active_patients: number;
  inactive_patients: number;
}

interface TestStats {
  total_tests: number;
  pending: number;
  in_progress: number;
  completed: number;
  cancelled: number;
}

interface DashboardData {
  patientStats: PatientStats;
  testStats: TestStats;
}

interface MetricCardProps {
  label: string;
  value: number | string;
  description: string;
  icon: ReactNode;
  accent: string;
  delay: number;
}

interface StatusRow {
  label: string;
  value: number;
  percentage: number;
  colour: string;
  description: string;
}

interface QuickAction {
  title: string;
  description: string;
  path: string;
  icon: ReactNode;
  accent: string;
}

const safeNumber = (
  value: unknown
): number => {
  const numberValue = Number(value);

  return Number.isFinite(numberValue)
    ? numberValue
    : 0;
};

const calculatePercentage = (
  value: number,
  total: number
): number => {
  if (total <= 0) {
    return 0;
  }

  return Math.min(
    100,
    Math.max(
      0,
      Math.round((value / total) * 100)
    )
  );
};

const fetchDashboardData =
  async (): Promise<DashboardData> => {
    const [
      patientResponse,
      testResponse,
    ] = await Promise.all([
      api.get<PatientStats>('/patients/stats'),
      api.get<TestStats>('/test-results/stats'),
    ]);

    return {
      patientStats: {
        total_patients: safeNumber(
          patientResponse.data.total_patients
        ),
        active_patients: safeNumber(
          patientResponse.data.active_patients
        ),
        inactive_patients: safeNumber(
          patientResponse.data.inactive_patients
        ),
      },

      testStats: {
        total_tests: safeNumber(
          testResponse.data.total_tests
        ),
        pending: safeNumber(
          testResponse.data.pending
        ),
        in_progress: safeNumber(
          testResponse.data.in_progress
        ),
        completed: safeNumber(
          testResponse.data.completed
        ),
        cancelled: safeNumber(
          testResponse.data.cancelled
        ),
      },
    };
  };

const MetricCard = ({
  label,
  value,
  description,
  icon,
  accent,
  delay,
}: MetricCardProps) => {
  return (
    <motion.div
      initial={{
        opacity: 0,
        y: 18,
      }}
      animate={{
        opacity: 1,
        y: 0,
      }}
      transition={{
        duration: 0.52,
        delay,
        ease: [0.22, 1, 0.36, 1],
      }}
    >
      <Card
        sx={{
          position: 'relative',
          height: '100%',
          overflow: 'hidden',
          transition:
            'transform 220ms ease, box-shadow 220ms ease, border-color 220ms ease',

          '&::before': {
            content: '""',
            position: 'absolute',
            top: 0,
            left: 0,
            width: '100%',
            height: 3,
            background:
              `linear-gradient(90deg, ${accent}, ${alpha(
                accent,
                0.24
              )})`,
          },

          '&::after': {
            content: '""',
            position: 'absolute',
            top: -65,
            right: -60,
            width: 145,
            height: 145,
            borderRadius: '50%',
            pointerEvents: 'none',
            background:
              `radial-gradient(circle, ${alpha(
                accent,
                0.12
              )}, transparent 70%)`,
          },

          '&:hover': {
            transform: 'translateY(-4px)',
            borderColor: alpha(accent, 0.2),
            boxShadow:
              `0 18px 42px ${alpha(
                accent,
                0.11
              )}`,
          },
        }}
      >
        <CardContent
          sx={{
            position: 'relative',
            zIndex: 1,
          }}
        >
          <Box
            sx={{
              display: 'flex',
              alignItems: 'flex-start',
              justifyContent: 'space-between',
              gap: 2,
            }}
          >
            <Box sx={{ minWidth: 0 }}>
              <Typography
                sx={{
                  color: 'text.secondary',
                  fontSize: '0.72rem',
                  lineHeight: 1.4,
                  fontWeight: 650,
                  letterSpacing: '0.04em',
                  textTransform: 'uppercase',
                }}
              >
                {label}
              </Typography>

              <Typography
                sx={{
                  mt: 1.35,
                  color: 'text.primary',
                  fontSize: {
                    xs: '2rem',
                    lg: '2.25rem',
                  },
                  lineHeight: 1,
                  fontWeight: 760,
                  letterSpacing: '-0.045em',
                }}
              >
                {value}
              </Typography>
            </Box>

            <Box
              sx={{
                width: 46,
                height: 46,
                flexShrink: 0,
                display: 'grid',
                placeItems: 'center',
                borderRadius: 3,
                color: accent,
                border:
                  `1px solid ${alpha(
                    accent,
                    0.1
                  )}`,
                backgroundColor:
                  alpha(accent, 0.075),

                '& svg': {
                  fontSize: 23,
                },
              }}
            >
              {icon}
            </Box>
          </Box>

          <Typography
            sx={{
              mt: 1.5,
              color: 'text.secondary',
              fontSize: '0.76rem',
              lineHeight: 1.55,
            }}
          >
            {description}
          </Typography>
        </CardContent>
      </Card>
    </motion.div>
  );
};

const DashboardLoading = () => {
  return (
    <Layout title="Overview">
      <Box
        sx={{
          display: 'grid',
          gap: 3,
        }}
      >
        <Skeleton
          variant="rounded"
          height={265}
          sx={{
            borderRadius: 5,
          }}
        />

        <Box
          sx={{
            display: 'grid',
            gridTemplateColumns: {
              xs: '1fr',
              sm: 'repeat(2, minmax(0, 1fr))',
              xl: 'repeat(4, minmax(0, 1fr))',
            },
            gap: 2.25,
          }}
        >
          {[0, 1, 2, 3].map((item) => (
            <Skeleton
              key={item}
              variant="rounded"
              height={172}
              sx={{
                borderRadius: 4,
              }}
            />
          ))}
        </Box>

        <Box
          sx={{
            display: 'grid',
            gridTemplateColumns: {
              xs: '1fr',
              xl: 'minmax(0, 1.45fr) minmax(340px, 0.55fr)',
            },
            gap: 2.5,
          }}
        >
          <Skeleton
            variant="rounded"
            height={430}
            sx={{
              borderRadius: 4,
            }}
          />

          <Skeleton
            variant="rounded"
            height={430}
            sx={{
              borderRadius: 4,
            }}
          />
        </Box>
      </Box>
    </Layout>
  );
};

const Dashboard = () => {
  const navigate = useNavigate();

  const {
    user,
    tenant,
  } = useAuth();

  const {
    data,
    isPending,
    isError,
    isFetching,
    refetch,
  } = useQuery({
    queryKey: [
      'tenant-dashboard-statistics',
      tenant?.domain,
    ],
    queryFn: fetchDashboardData,
  });

  const dateLabel = useMemo(() => {
    return new Intl.DateTimeFormat(
      undefined,
      {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
      }
    ).format(new Date());
  }, []);

  if (isPending) {
    return <DashboardLoading />;
  }

  if (isError || !data) {
    return (
      <Layout title="Overview">
        <Box
          sx={{
            minHeight: 440,
            display: 'grid',
            placeItems: 'center',
          }}
        >
          <Alert
            severity="error"
            action={
              <Button
                color="inherit"
                size="small"
                startIcon={<RefreshRounded />}
                onClick={() => {
                  void refetch();
                }}
              >
                Retry
              </Button>
            }
            sx={{
              width: '100%',
              maxWidth: 720,
            }}
          >
            Rezhel could not load the current laboratory
            statistics. Your stored records have not been
            changed.
          </Alert>
        </Box>
      </Layout>
    );
  }

  const {
    patientStats,
    testStats,
  } = data;

  const totalPatients =
    patientStats.total_patients;

  const activePatients =
    patientStats.active_patients;

  const totalTests =
    testStats.total_tests;

  const openWork =
    testStats.pending +
    testStats.in_progress;

  const completionRate =
    calculatePercentage(
      testStats.completed,
      totalTests
    );

  const activePatientRate =
    calculatePercentage(
      activePatients,
      totalPatients
    );

  const firstName =
    user?.name?.trim().split(/\s+/)[0] ??
    'there';

  const canManageUsers = [
    'admin',
    'superadmin',
  ].includes(user?.role ?? '');

  const statusRows: StatusRow[] = [
    {
      label: 'Completed',
      value: testStats.completed,
      percentage: calculatePercentage(
        testStats.completed,
        totalTests
      ),
      colour: '#079455',
      description:
        'Results that have completed the recorded workflow.',
    },
    {
      label: 'In progress',
      value: testStats.in_progress,
      percentage: calculatePercentage(
        testStats.in_progress,
        totalTests
      ),
      colour: '#1570EF',
      description:
        'Tests currently moving through laboratory processing.',
    },
    {
      label: 'Pending',
      value: testStats.pending,
      percentage: calculatePercentage(
        testStats.pending,
        totalTests
      ),
      colour: '#DC6803',
      description:
        'Tests awaiting the next authorised workflow action.',
    },
    {
      label: 'Cancelled',
      value: testStats.cancelled,
      percentage: calculatePercentage(
        testStats.cancelled,
        totalTests
      ),
      colour: '#D92D20',
      description:
        'Tests recorded as cancelled in the current workspace.',
    },
  ];

  const quickActions: QuickAction[] = [
    {
      title: 'Register patient',
      description:
        'Create a new patient record in this laboratory.',
      path: '/patients/new',
      icon: <PersonAddAlt1Rounded />,
      accent: '#246BFD',
    },
    {
      title: 'Create test',
      description:
        'Add a test to the laboratory catalogue.',
      path: '/lab-tests/new',
      icon: <BiotechRounded />,
      accent: '#0891B2',
    },
    {
      title: 'Review results',
      description:
        'Open the current test-result workspace.',
      path: '/test-results',
      icon: <FactCheckRounded />,
      accent: '#7F56D9',
    },
    {
      title: 'Patient records',
      description:
        'Search and manage existing patient records.',
      path: '/patients',
      icon: <PeopleAltRounded />,
      accent: '#079455',
    },
    ...(canManageUsers
      ? [
          {
            title: 'Manage access',
            description:
              'Maintain users and laboratory permissions.',
            path: '/users',
            icon: <GroupsRounded />,
            accent: '#B54708',
          },
        ]
      : []),
  ];

  return (
    <Layout title="Overview">
      <Box
        sx={{
          display: 'grid',
          gap: 3,
        }}
      >
        <motion.div
          initial={{
            opacity: 0,
            y: 18,
          }}
          animate={{
            opacity: 1,
            y: 0,
          }}
          transition={{
            duration: 0.58,
            ease: [0.22, 1, 0.36, 1],
          }}
        >
          <Card
            sx={{
              position: 'relative',
              overflow: 'hidden',
              color: '#FFFFFF',
              border: 0,
              borderRadius: 5,
              background:
                'linear-gradient(135deg, #07111F 0%, #0A1A2D 52%, #0B3042 100%)',
              boxShadow:
                '0 28px 70px rgba(7, 17, 31, 0.18)',

              '&::before': {
                content: '""',
                position: 'absolute',
                top: -185,
                right: -130,
                width: 450,
                height: 450,
                borderRadius: '50%',
                background:
                  'radial-gradient(circle, rgba(37, 99, 235, 0.34), rgba(37, 99, 235, 0) 68%)',
              },

              '&::after': {
                content: '""',
                position: 'absolute',
                bottom: -230,
                left: '20%',
                width: 440,
                height: 440,
                borderRadius: '50%',
                background:
                  'radial-gradient(circle, rgba(8, 145, 178, 0.24), rgba(8, 145, 178, 0) 68%)',
              },
            }}
          >
            <Box
              sx={{
                position: 'relative',
                zIndex: 1,
                display: 'grid',
                gridTemplateColumns: {
                  xs: '1fr',
                  lg: 'minmax(0, 1fr) 320px',
                },
                gap: {
                  xs: 4,
                  lg: 6,
                },
                p: {
                  xs: 3,
                  sm: 4,
                  lg: 5,
                },
              }}
            >
              <Box
                sx={{
                  display: 'flex',
                  flexDirection: 'column',
                  justifyContent: 'center',
                }}
              >
                <Box
                  sx={{
                    display: 'flex',
                    alignItems: 'center',
                    flexWrap: 'wrap',
                    gap: 1,
                    mb: 2.5,
                  }}
                >
                  <Chip
                    icon={
                      <VerifiedRounded
                        sx={{
                          fontSize:
                            '17px !important',
                        }}
                      />
                    }
                    label="Verified operational data"
                    sx={{
                      color: '#A5F3FC',
                      border:
                        '1px solid rgba(103, 232, 249, 0.16)',
                      backgroundColor:
                        'rgba(8, 145, 178, 0.11)',

                      '& .MuiChip-icon': {
                        color: '#67E8F9',
                      },
                    }}
                  />

                  {isFetching && (
                    <Chip
                      label="Refreshing"
                      sx={{
                        color:
                          'rgba(255,255,255,0.66)',
                        backgroundColor:
                          'rgba(255,255,255,0.06)',
                      }}
                    />
                  )}
                </Box>

                <Typography
                  component="h2"
                  sx={{
                    maxWidth: 730,
                    fontSize: {
                      xs: '2rem',
                      sm: '2.65rem',
                      lg: '3rem',
                    },
                    lineHeight: 1.05,
                    fontWeight: 750,
                    letterSpacing: '-0.05em',
                  }}
                >
                  Welcome back, {firstName}.
                </Typography>

                <Typography
                  sx={{
                    maxWidth: 650,
                    mt: 2,
                    color:
                      'rgba(255, 255, 255, 0.61)',
                    fontSize: {
                      xs: '0.88rem',
                      sm: '0.96rem',
                    },
                    lineHeight: 1.75,
                  }}
                >
                  Here is the current operational
                  position of{' '}
                  <Box
                    component="span"
                    sx={{
                      color: '#FFFFFF',
                      fontWeight: 640,
                    }}
                  >
                    {tenant?.name ??
                      'your laboratory'}
                  </Box>
                  . Every figure below comes from the
                  active tenant workspace.
                </Typography>

                <Box
                  sx={{
                    display: 'flex',
                    alignItems: 'center',
                    flexWrap: 'wrap',
                    gap: 1.25,
                    mt: 3.25,
                  }}
                >
                  <Chip
                    icon={<DomainRounded />}
                    label={
                      tenant?.domain ??
                      'Rezhel platform'
                    }
                    sx={{
                      color:
                        'rgba(255,255,255,0.72)',
                      backgroundColor:
                        'rgba(255,255,255,0.065)',

                      '& .MuiChip-icon': {
                        color: '#8EDDF0',
                      },
                    }}
                  />

                  <Typography
                    sx={{
                      color:
                        'rgba(255,255,255,0.43)',
                      fontSize: '0.72rem',
                    }}
                  >
                    {dateLabel}
                  </Typography>
                </Box>
              </Box>

              <Box
                sx={{
                  display: 'grid',
                  placeItems: 'center',
                  p: 3,
                  border:
                    '1px solid rgba(255,255,255,0.075)',
                  borderRadius: 4.5,
                  background:
                    'linear-gradient(145deg, rgba(255,255,255,0.075), rgba(255,255,255,0.025))',
                  backdropFilter: 'blur(18px)',
                }}
              >
                <Box
                  sx={{
                    position: 'relative',
                    width: 154,
                    height: 154,
                    display: 'grid',
                    placeItems: 'center',
                  }}
                >
                  <CircularProgress
                    variant="determinate"
                    value={100}
                    size={154}
                    thickness={2.8}
                    sx={{
                      position: 'absolute',
                      color:
                        'rgba(255,255,255,0.08)',
                    }}
                  />

                  <CircularProgress
                    variant="determinate"
                    value={completionRate}
                    size={154}
                    thickness={2.8}
                    sx={{
                      position: 'absolute',
                      color: '#67E8F9',
                      filter:
                        'drop-shadow(0 0 8px rgba(103, 232, 249, 0.30))',
                    }}
                  />

                  <Box sx={{ textAlign: 'center' }}>
                    <Typography
                      sx={{
                        fontSize: '2.15rem',
                        lineHeight: 1,
                        fontWeight: 760,
                        letterSpacing: '-0.05em',
                      }}
                    >
                      {completionRate}%
                    </Typography>

                    <Typography
                      sx={{
                        mt: 0.8,
                        color:
                          'rgba(255,255,255,0.46)',
                        fontSize: '0.67rem',
                        fontWeight: 620,
                        letterSpacing: '0.055em',
                        textTransform: 'uppercase',
                      }}
                    >
                      Completion
                    </Typography>
                  </Box>
                </Box>

                <Box
                  sx={{
                    width: '100%',
                    display: 'grid',
                    gridTemplateColumns:
                      'repeat(2, minmax(0, 1fr))',
                    gap: 1,
                    mt: 3,
                  }}
                >
                  <Box
                    sx={{
                      p: 1.5,
                      textAlign: 'center',
                      borderRadius: 2.5,
                      backgroundColor:
                        'rgba(255,255,255,0.045)',
                    }}
                  >
                    <Typography
                      sx={{
                        fontSize: '1.25rem',
                        lineHeight: 1,
                        fontWeight: 720,
                      }}
                    >
                      {openWork}
                    </Typography>

                    <Typography
                      sx={{
                        mt: 0.6,
                        color:
                          'rgba(255,255,255,0.42)',
                        fontSize: '0.64rem',
                      }}
                    >
                      Open workload
                    </Typography>
                  </Box>

                  <Box
                    sx={{
                      p: 1.5,
                      textAlign: 'center',
                      borderRadius: 2.5,
                      backgroundColor:
                        'rgba(255,255,255,0.045)',
                    }}
                  >
                    <Typography
                      sx={{
                        fontSize: '1.25rem',
                        lineHeight: 1,
                        fontWeight: 720,
                      }}
                    >
                      {totalTests}
                    </Typography>

                    <Typography
                      sx={{
                        mt: 0.6,
                        color:
                          'rgba(255,255,255,0.42)',
                        fontSize: '0.64rem',
                      }}
                    >
                      Tests recorded
                    </Typography>
                  </Box>
                </Box>
              </Box>
            </Box>
          </Card>
        </motion.div>

        <Box
          sx={{
            display: 'grid',
            gridTemplateColumns: {
              xs: '1fr',
              sm: 'repeat(2, minmax(0, 1fr))',
              xl: 'repeat(4, minmax(0, 1fr))',
            },
            gap: 2.25,
          }}
        >
          <MetricCard
            label="Patient records"
            value={totalPatients}
            description={`${activePatients} active and ${patientStats.inactive_patients} inactive records.`}
            icon={<PeopleAltRounded />}
            accent="#246BFD"
            delay={0.05}
          />

          <MetricCard
            label="Tests recorded"
            value={totalTests}
            description={`${testStats.completed} completed across the current workspace.`}
            icon={<ScienceRounded />}
            accent="#0891B2"
            delay={0.1}
          />

          <MetricCard
            label="Open workload"
            value={openWork}
            description={`${testStats.pending} pending and ${testStats.in_progress} currently in progress.`}
            icon={<HourglassTopRounded />}
            accent="#DC6803"
            delay={0.15}
          />

          <MetricCard
            label="Completion rate"
            value={`${completionRate}%`}
            description={`${testStats.completed} of ${totalTests} recorded tests are completed.`}
            icon={<TaskAltRounded />}
            accent="#079455"
            delay={0.2}
          />
        </Box>

        <Box
          sx={{
            display: 'grid',
            gridTemplateColumns: {
              xs: '1fr',
              xl: 'minmax(0, 1.45fr) minmax(350px, 0.55fr)',
            },
            gap: 2.5,
            alignItems: 'start',
          }}
        >
          <Card>
            <CardContent>
              <Box
                sx={{
                  display: 'flex',
                  alignItems: 'flex-start',
                  justifyContent: 'space-between',
                  flexWrap: 'wrap',
                  gap: 2,
                  mb: 3,
                }}
              >
                <Box>
                  <Typography
                    variant="overline"
                    color="primary.main"
                  >
                    Clinical workflow
                  </Typography>

                  <Typography
                    component="h3"
                    sx={{
                      mt: 0.6,
                      fontSize: '1.35rem',
                      fontWeight: 710,
                      letterSpacing: '-0.025em',
                    }}
                  >
                    Operational pulse
                  </Typography>

                  <Typography
                    sx={{
                      mt: 0.7,
                      color: 'text.secondary',
                      fontSize: '0.78rem',
                      lineHeight: 1.6,
                    }}
                  >
                    Current distribution of all test
                    records in this tenant.
                  </Typography>
                </Box>

                <Chip
                  icon={<VerifiedRounded />}
                  label={`${totalTests} verified records`}
                  sx={{
                    color: 'primary.dark',
                    backgroundColor:
                      alpha('#246BFD', 0.07),

                    '& .MuiChip-icon': {
                      color: 'primary.main',
                    },
                  }}
                />
              </Box>

              <Box
                sx={{
                  display: 'flex',
                  width: '100%',
                  height: 12,
                  overflow: 'hidden',
                  mb: 3.5,
                  borderRadius: 999,
                  backgroundColor:
                    alpha('#0B1220', 0.05),
                }}
              >
                {totalTests > 0 ? (
                  statusRows.map((status) => (
                    <Box
                      key={status.label}
                      title={`${status.label}: ${status.value}`}
                      sx={{
                        width:
                          `${status.percentage}%`,
                        minWidth:
                          status.value > 0
                            ? 3
                            : 0,
                        backgroundColor:
                          status.colour,
                        transition:
                          'width 420ms ease',
                      }}
                    />
                  ))
                ) : (
                  <Box
                    sx={{
                      width: '100%',
                      backgroundColor:
                        alpha('#0B1220', 0.04),
                    }}
                  />
                )}
              </Box>

              <Box
                sx={{
                  display: 'grid',
                  gap: 2.75,
                }}
              >
                {statusRows.map((status) => (
                  <Box key={status.label}>
                    <Box
                      sx={{
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent:
                          'space-between',
                        gap: 2,
                        mb: 1,
                      }}
                    >
                      <Box
                        sx={{
                          display: 'flex',
                          alignItems: 'center',
                          minWidth: 0,
                          gap: 1.25,
                        }}
                      >
                        <Box
                          sx={{
                            width: 9,
                            height: 9,
                            flexShrink: 0,
                            borderRadius: '50%',
                            backgroundColor:
                              status.colour,
                            boxShadow:
                              `0 0 0 5px ${alpha(
                                status.colour,
                                0.08
                              )}`,
                          }}
                        />

                        <Box sx={{ minWidth: 0 }}>
                          <Typography
                            sx={{
                              fontSize: '0.8rem',
                              fontWeight: 650,
                            }}
                          >
                            {status.label}
                          </Typography>

                          <Typography
                            sx={{
                              mt: 0.2,
                              color:
                                'text.secondary',
                              fontSize: '0.68rem',
                              lineHeight: 1.5,
                            }}
                          >
                            {status.description}
                          </Typography>
                        </Box>
                      </Box>

                      <Box
                        sx={{
                          flexShrink: 0,
                          textAlign: 'right',
                        }}
                      >
                        <Typography
                          sx={{
                            fontSize: '0.9rem',
                            fontWeight: 710,
                          }}
                        >
                          {status.value}
                        </Typography>

                        <Typography
                          sx={{
                            color:
                              'text.secondary',
                            fontSize: '0.65rem',
                          }}
                        >
                          {status.percentage}%
                        </Typography>
                      </Box>
                    </Box>

                    <LinearProgress
                      variant="determinate"
                      value={status.percentage}
                      sx={{
                        height: 6,
                        borderRadius: 999,
                        backgroundColor:
                          alpha(status.colour, 0.08),

                        '& .MuiLinearProgress-bar': {
                          borderRadius: 999,
                          backgroundColor:
                            status.colour,
                        },
                      }}
                    />
                  </Box>
                ))}
              </Box>
            </CardContent>
          </Card>

          <Box
            sx={{
              display: 'grid',
              gap: 2.5,
            }}
          >
            <Card>
              <CardContent>
                <Typography
                  variant="overline"
                  color="secondary.main"
                >
                  Priority actions
                </Typography>

                <Typography
                  component="h3"
                  sx={{
                    mt: 0.6,
                    mb: 2.5,
                    fontSize: '1.18rem',
                    fontWeight: 700,
                    letterSpacing: '-0.02em',
                  }}
                >
                  Move work forward
                </Typography>

                <Box
                  sx={{
                    display: 'grid',
                    gap: 1,
                  }}
                >
                  {quickActions.map((action) => (
                    <Button
                      key={action.title}
                      fullWidth
                      onClick={() => {
                        navigate(action.path);
                      }}
                      endIcon={
                        <ArrowForwardRounded />
                      }
                      sx={{
                        minHeight: 68,
                        justifyContent:
                          'flex-start',
                        px: 1.5,
                        py: 1.25,
                        color: 'text.primary',
                        border: '1px solid',
                        borderColor: 'divider',
                        backgroundColor:
                          'background.paper',

                        '& .MuiButton-endIcon': {
                          ml: 'auto',
                          color:
                            'text.disabled',
                        },

                        '&:hover': {
                          transform:
                            'translateX(3px)',
                          borderColor:
                            alpha(
                              action.accent,
                              0.22
                            ),
                          backgroundColor:
                            alpha(
                              action.accent,
                              0.035
                            ),

                          '& .MuiButton-endIcon': {
                            color:
                              action.accent,
                          },
                        },
                      }}
                    >
                      <Box
                        sx={{
                          width: 40,
                          height: 40,
                          flexShrink: 0,
                          display: 'grid',
                          placeItems: 'center',
                          mr: 1.4,
                          borderRadius: 2.4,
                          color: action.accent,
                          backgroundColor:
                            alpha(
                              action.accent,
                              0.075
                            ),

                          '& svg': {
                            fontSize: 20,
                          },
                        }}
                      >
                        {action.icon}
                      </Box>

                      <Box
                        sx={{
                          minWidth: 0,
                          textAlign: 'left',
                        }}
                      >
                        <Typography
                          sx={{
                            fontSize: '0.76rem',
                            fontWeight: 660,
                          }}
                        >
                          {action.title}
                        </Typography>

                        <Typography
                          sx={{
                            mt: 0.25,
                            color:
                              'text.secondary',
                            fontSize: '0.65rem',
                            lineHeight: 1.45,
                          }}
                        >
                          {action.description}
                        </Typography>
                      </Box>
                    </Button>
                  ))}
                </Box>
              </CardContent>
            </Card>

            <Card
              sx={{
                background:
                  'linear-gradient(145deg, rgba(36,107,253,0.055), rgba(8,145,178,0.025))',
              }}
            >
              <CardContent>
                <Box
                  sx={{
                    display: 'flex',
                    alignItems: 'center',
                    gap: 1.25,
                  }}
                >
                  <Box
                    sx={{
                      width: 42,
                      height: 42,
                      display: 'grid',
                      placeItems: 'center',
                      borderRadius: 2.7,
                      color: 'primary.main',
                      backgroundColor:
                        alpha(
                          '#246BFD',
                          0.075
                        ),
                    }}
                  >
                    <ShieldRounded />
                  </Box>

                  <Box sx={{ minWidth: 0 }}>
                    <Typography
                      sx={{
                        fontSize: '0.78rem',
                        fontWeight: 680,
                      }}
                    >
                      Tenant-scoped workspace
                    </Typography>

                    <Typography
                      sx={{
                        mt: 0.25,
                        overflow: 'hidden',
                        color:
                          'text.secondary',
                        fontSize: '0.66rem',
                        textOverflow:
                          'ellipsis',
                        whiteSpace: 'nowrap',
                      }}
                    >
                      {tenant?.name ??
                        'Rezhel Platform'}
                    </Typography>
                  </Box>
                </Box>

                <Box
                  sx={{
                    display: 'grid',
                    gridTemplateColumns:
                      'repeat(2, minmax(0, 1fr))',
                    gap: 1,
                    mt: 2.25,
                  }}
                >
                  <Box
                    sx={{
                      p: 1.5,
                      borderRadius: 2.5,
                      backgroundColor:
                        alpha('#FFFFFF', 0.72),
                    }}
                  >
                    <Typography
                      sx={{
                        color:
                          'text.secondary',
                        fontSize: '0.62rem',
                        textTransform:
                          'uppercase',
                        letterSpacing:
                          '0.055em',
                        fontWeight: 650,
                      }}
                    >
                      Active patients
                    </Typography>

                    <Typography
                      sx={{
                        mt: 0.7,
                        fontSize: '1.2rem',
                        fontWeight: 720,
                      }}
                    >
                      {activePatientRate}%
                    </Typography>
                  </Box>

                  <Box
                    sx={{
                      p: 1.5,
                      borderRadius: 2.5,
                      backgroundColor:
                        alpha('#FFFFFF', 0.72),
                    }}
                  >
                    <Typography
                      sx={{
                        color:
                          'text.secondary',
                        fontSize: '0.62rem',
                        textTransform:
                          'uppercase',
                        letterSpacing:
                          '0.055em',
                        fontWeight: 650,
                      }}
                    >
                      Open tests
                    </Typography>

                    <Typography
                      sx={{
                        mt: 0.7,
                        fontSize: '1.2rem',
                        fontWeight: 720,
                      }}
                    >
                      {openWork}
                    </Typography>
                  </Box>
                </Box>

                <Button
                  fullWidth
                  variant="outlined"
                  startIcon={<AddRounded />}
                  onClick={() => {
                    navigate('/patients/new');
                  }}
                  sx={{
                    mt: 2,
                  }}
                >
                  Start a new patient record
                </Button>
              </CardContent>
            </Card>
          </Box>
        </Box>
      </Box>
    </Layout>
  );
};

export default Dashboard;