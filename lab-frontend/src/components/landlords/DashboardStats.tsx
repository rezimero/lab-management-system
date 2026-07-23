import {
  ReactNode,
  useMemo,
} from 'react';

import ApartmentRounded from '@mui/icons-material/ApartmentRounded';
import ArrowForwardRounded from '@mui/icons-material/ArrowForwardRounded';
import AssignmentTurnedInRounded from '@mui/icons-material/AssignmentTurnedInRounded';
import BiotechRounded from '@mui/icons-material/BiotechRounded';
import CheckCircleRounded from '@mui/icons-material/CheckCircleRounded';
import GroupsRounded from '@mui/icons-material/GroupsRounded';
import PeopleAltRounded from '@mui/icons-material/PeopleAltRounded';
import RefreshRounded from '@mui/icons-material/RefreshRounded';
import ShieldRounded from '@mui/icons-material/ShieldRounded';
import VerifiedRounded from '@mui/icons-material/VerifiedRounded';

import {
  Alert,
  Box,
  Button,
  Card,
  CardContent,
  Chip,
  LinearProgress,
  Skeleton,
  Typography,
  alpha,
} from '@mui/material';

import {
  useQuery,
} from '@tanstack/react-query';

import {
  motion,
} from 'framer-motion';

import {
  useNavigate,
} from 'react-router-dom';

import api from '../../api/client';

interface TenantPlatformStats {
  id: number;
  name: string;
  domain: string;
  is_active: boolean;

  stats?: {
    users?: number;
    patients?: number;
    lab_tests?: number;
    test_results?: number;
  };
}

interface PlatformDashboardStats {
  total_tenants: number;
  total_users: number;
  total_patients: number;
  total_lab_tests: number;
  total_test_results: number;
  tenants: TenantPlatformStats[];
}

interface PlatformMetricProps {
  label: string;
  value: number;
  description: string;
  icon: ReactNode;
  accent: string;
  delay: number;
}

const safeNumber = (
  value: unknown
): number => {
  const parsedValue = Number(value);

  return Number.isFinite(parsedValue)
    ? parsedValue
    : 0;
};

const fetchPlatformStats =
  async (): Promise<PlatformDashboardStats> => {
    const response =
      await api.get<PlatformDashboardStats>(
        '/landlord/dashboard/stats'
      );

    const data = response.data;

    return {
      total_tenants: safeNumber(
        data.total_tenants
      ),

      total_users: safeNumber(
        data.total_users
      ),

      total_patients: safeNumber(
        data.total_patients
      ),

      total_lab_tests: safeNumber(
        data.total_lab_tests
      ),

      total_test_results: safeNumber(
        data.total_test_results
      ),

      tenants: Array.isArray(data.tenants)
        ? data.tenants
        : [],
    };
  };

const PlatformMetric = ({
  label,
  value,
  description,
  icon,
  accent,
  delay,
}: PlatformMetricProps) => {
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
                0.22
              )})`,
          },

          '&::after': {
            content: '""',
            position: 'absolute',
            top: -60,
            right: -55,
            width: 140,
            height: 140,
            borderRadius: '50%',
            pointerEvents: 'none',
            background:
              `radial-gradient(circle, ${alpha(
                accent,
                0.12
              )}, transparent 70%)`,
          },

          '&:hover': {
            transform:
              'translateY(-4px)',
            borderColor:
              alpha(accent, 0.2),
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
              justifyContent:
                'space-between',
              gap: 2,
            }}
          >
            <Box sx={{ minWidth: 0 }}>
              <Typography
                sx={{
                  color: 'text.secondary',
                  fontSize: '0.7rem',
                  fontWeight: 680,
                  letterSpacing: '0.055em',
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
                {value.toLocaleString()}
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
              fontSize: '0.75rem',
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

const DashboardStatsLoading = () => {
  return (
    <Box
      sx={{
        display: 'grid',
        gap: 3,
      }}
    >
      <Skeleton
        variant="rounded"
        height={300}
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
            xl: 'repeat(5, minmax(0, 1fr))',
          },
          gap: 2,
        }}
      >
        {[0, 1, 2, 3, 4].map(
          (item) => (
            <Skeleton
              key={item}
              variant="rounded"
              height={170}
              sx={{
                borderRadius: 4,
              }}
            />
          )
        )}
      </Box>

      <Skeleton
        variant="rounded"
        height={420}
        sx={{
          borderRadius: 4,
        }}
      />
    </Box>
  );
};

const DashboardStats = () => {
  const navigate = useNavigate();

  const {
    data,
    isPending,
    isError,
    isFetching,
    refetch,
  } = useQuery({
    queryKey: [
      'rezhel-landlord-dashboard',
    ],
    queryFn: fetchPlatformStats,
    staleTime: 30_000,
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
    return <DashboardStatsLoading />;
  }

  if (isError || !data) {
    return (
      <Box
        sx={{
          minHeight: 460,
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
            maxWidth: 760,
          }}
        >
          Rezhel could not load the current platform
          statistics. No tenant records have been
          changed.
        </Alert>
      </Box>
    );
  }

  const tenants = data.tenants;

  const activeTenants =
    tenants.filter(
      (tenant) => tenant.is_active
    );

  const suspendedTenants =
    Math.max(
      0,
      data.total_tenants -
        activeTenants.length
    );

  const activeTenantRate =
    data.total_tenants > 0
      ? Math.round(
          (activeTenants.length /
            data.total_tenants) *
            100
        )
      : 0;

  const averageUsersPerTenant =
    data.total_tenants > 0
      ? Math.round(
          data.total_users /
            data.total_tenants
        )
      : 0;

  const rankedTenants = [
    ...tenants,
  ]
    .sort((left, right) => {
      const leftActivity =
        safeNumber(
          left.stats?.users
        ) +
        safeNumber(
          left.stats?.patients
        ) +
        safeNumber(
          left.stats?.lab_tests
        ) +
        safeNumber(
          left.stats?.test_results
        );

      const rightActivity =
        safeNumber(
          right.stats?.users
        ) +
        safeNumber(
          right.stats?.patients
        ) +
        safeNumber(
          right.stats?.lab_tests
        ) +
        safeNumber(
          right.stats?.test_results
        );

      return rightActivity - leftActivity;
    })
    .slice(0, 6);

  return (
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
              'linear-gradient(135deg, #07111F 0%, #161334 50%, #0B3042 100%)',
            boxShadow:
              '0 30px 80px rgba(7,17,31,0.2)',

            '&::before': {
              content: '""',
              position: 'absolute',
              top: -210,
              right: -110,
              width: 510,
              height: 510,
              borderRadius: '50%',
              background:
                'radial-gradient(circle, rgba(127,86,217,0.4), rgba(127,86,217,0) 69%)',
            },

            '&::after': {
              content: '""',
              position: 'absolute',
              bottom: -240,
              left: '20%',
              width: 480,
              height: 480,
              borderRadius: '50%',
              background:
                'radial-gradient(circle, rgba(8,145,178,0.28), rgba(8,145,178,0) 68%)',
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
                lg: 'minmax(0,1fr) 360px',
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
                  icon={<VerifiedRounded />}
                  label="Verified platform data"
                  sx={{
                    color: '#DDD6FE',
                    border:
                      '1px solid rgba(196,181,253,0.18)',
                    backgroundColor:
                      'rgba(127,86,217,0.13)',

                    '& .MuiChip-icon': {
                      color: '#C4B5FD',
                    },
                  }}
                />

                {isFetching && (
                  <Chip
                    label="Refreshing"
                    sx={{
                      color:
                        'rgba(255,255,255,0.68)',
                      backgroundColor:
                        'rgba(255,255,255,0.06)',
                    }}
                  />
                )}
              </Box>

              <Typography
                component="h2"
                sx={{
                  maxWidth: 760,
                  fontSize: {
                    xs: '2rem',
                    sm: '2.75rem',
                    lg: '3.15rem',
                  },
                  lineHeight: 1.04,
                  fontWeight: 760,
                  letterSpacing: '-0.052em',
                }}
              >
                Command the entire
                Rezhel ecosystem.
              </Typography>

              <Typography
                sx={{
                  maxWidth: 670,
                  mt: 2,
                  color:
                    'rgba(255,255,255,0.61)',
                  fontSize: {
                    xs: '0.88rem',
                    sm: '0.97rem',
                  },
                  lineHeight: 1.78,
                }}
              >
                Govern laboratories, users and
                clinical operations from one secure
                landlord environment. Every figure
                below is derived from the current
                Rezhel platform state.
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
                  icon={<ShieldRounded />}
                  label="Landlord authority active"
                  sx={{
                    color:
                      'rgba(255,255,255,0.76)',
                    backgroundColor:
                      'rgba(255,255,255,0.065)',

                    '& .MuiChip-icon': {
                      color: '#A5F3FC',
                    },
                  }}
                />

                <Typography
                  sx={{
                    color:
                      'rgba(255,255,255,0.42)',
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
                gap: 1.25,
                p: 2.5,
                border:
                  '1px solid rgba(255,255,255,0.075)',
                borderRadius: 4.5,
                background:
                  'linear-gradient(145deg, rgba(255,255,255,0.08), rgba(255,255,255,0.025))',
                backdropFilter: 'blur(18px)',
              }}
            >
              <Box
                sx={{
                  p: 2,
                  borderRadius: 3,
                  backgroundColor:
                    'rgba(255,255,255,0.045)',
                }}
              >
                <Typography
                  sx={{
                    color:
                      'rgba(255,255,255,0.44)',
                    fontSize: '0.65rem',
                    fontWeight: 660,
                    letterSpacing: '0.06em',
                    textTransform: 'uppercase',
                  }}
                >
                  Active tenant rate
                </Typography>

                <Typography
                  sx={{
                    mt: 1,
                    fontSize: '2.4rem',
                    lineHeight: 1,
                    fontWeight: 760,
                    letterSpacing: '-0.05em',
                  }}
                >
                  {activeTenantRate}%
                </Typography>

                <LinearProgress
                  variant="determinate"
                  value={activeTenantRate}
                  sx={{
                    height: 7,
                    mt: 1.6,
                    borderRadius: 999,
                    backgroundColor:
                      'rgba(255,255,255,0.08)',

                    '& .MuiLinearProgress-bar': {
                      borderRadius: 999,
                      background:
                        'linear-gradient(90deg, #A78BFA, #67E8F9)',
                    },
                  }}
                />
              </Box>

              <Box
                sx={{
                  display: 'grid',
                  gridTemplateColumns:
                    'repeat(2, minmax(0,1fr))',
                  gap: 1,
                }}
              >
                <Box
                  sx={{
                    p: 1.5,
                    borderRadius: 2.7,
                    textAlign: 'center',
                    backgroundColor:
                      'rgba(255,255,255,0.04)',
                  }}
                >
                  <Typography
                    sx={{
                      fontSize: '1.35rem',
                      fontWeight: 730,
                    }}
                  >
                    {activeTenants.length}
                  </Typography>

                  <Typography
                    sx={{
                      mt: 0.4,
                      color:
                        'rgba(255,255,255,0.4)',
                      fontSize: '0.63rem',
                    }}
                  >
                    Active tenants
                  </Typography>
                </Box>

                <Box
                  sx={{
                    p: 1.5,
                    borderRadius: 2.7,
                    textAlign: 'center',
                    backgroundColor:
                      'rgba(255,255,255,0.04)',
                  }}
                >
                  <Typography
                    sx={{
                      fontSize: '1.35rem',
                      fontWeight: 730,
                    }}
                  >
                    {averageUsersPerTenant}
                  </Typography>

                  <Typography
                    sx={{
                      mt: 0.4,
                      color:
                        'rgba(255,255,255,0.4)',
                      fontSize: '0.63rem',
                    }}
                  >
                    Users per tenant
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
            sm: 'repeat(2, minmax(0,1fr))',
            xl: 'repeat(5, minmax(0,1fr))',
          },
          gap: 2,
        }}
      >
        <PlatformMetric
          label="Laboratories"
          value={data.total_tenants}
          description={`${activeTenants.length} active and ${suspendedTenants} suspended tenants.`}
          icon={<ApartmentRounded />}
          accent="#7F56D9"
          delay={0.05}
        />

        <PlatformMetric
          label="Platform users"
          value={data.total_users}
          description="Tenant users currently recorded across Rezhel."
          icon={<GroupsRounded />}
          accent="#246BFD"
          delay={0.1}
        />

        <PlatformMetric
          label="Patient records"
          value={data.total_patients}
          description="Patient records held within tenant boundaries."
          icon={<PeopleAltRounded />}
          accent="#079455"
          delay={0.15}
        />

        <PlatformMetric
          label="Lab tests"
          value={data.total_lab_tests}
          description="Tests configured across tenant catalogues."
          icon={<BiotechRounded />}
          accent="#0891B2"
          delay={0.2}
        />

        <PlatformMetric
          label="Test results"
          value={data.total_test_results}
          description="Clinical results recorded throughout the platform."
          icon={
            <AssignmentTurnedInRounded />
          }
          accent="#DC6803"
          delay={0.25}
        />
      </Box>

      <Box
        sx={{
          display: 'grid',
          gridTemplateColumns: {
            xs: '1fr',
            xl: 'minmax(0,1.45fr) minmax(350px,0.55fr)',
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
                justifyContent:
                  'space-between',
                flexWrap: 'wrap',
                gap: 2,
                mb: 3,
              }}
            >
              <Box>
                <Typography
                  variant="overline"
                  sx={{
                    color: '#6941C6',
                  }}
                >
                  Tenant portfolio
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
                  Laboratory activity
                </Typography>

                <Typography
                  sx={{
                    mt: 0.65,
                    color: 'text.secondary',
                    fontSize: '0.76rem',
                  }}
                >
                  Verified operational footprint
                  across the most active tenants.
                </Typography>
              </Box>

              <Button
                variant="outlined"
                endIcon={
                  <ArrowForwardRounded />
                }
                onClick={() => {
                  navigate(
                    '/landlord/tenants'
                  );
                }}
              >
                Manage tenants
              </Button>
            </Box>

            {rankedTenants.length === 0 ? (
              <Box
                sx={{
                  minHeight: 260,
                  display: 'grid',
                  placeItems: 'center',
                  textAlign: 'center',
                }}
              >
                <Box>
                  <ApartmentRounded
                    sx={{
                      color: 'text.disabled',
                      fontSize: 42,
                    }}
                  />

                  <Typography
                    sx={{
                      mt: 1.5,
                      fontWeight: 670,
                    }}
                  >
                    No tenant activity yet
                  </Typography>

                  <Typography
                    sx={{
                      mt: 0.5,
                      color: 'text.secondary',
                      fontSize: '0.75rem',
                    }}
                  >
                    Tenant statistics will appear
                    here after laboratories are
                    provisioned.
                  </Typography>
                </Box>
              </Box>
            ) : (
              <Box
                sx={{
                  display: 'grid',
                  gap: 1.2,
                }}
              >
                {rankedTenants.map(
                  (tenant) => {
                    const tenantStats =
                      tenant.stats ?? {};

                    const activityTotal =
                      safeNumber(
                        tenantStats.users
                      ) +
                      safeNumber(
                        tenantStats.patients
                      ) +
                      safeNumber(
                        tenantStats.lab_tests
                      ) +
                      safeNumber(
                        tenantStats.test_results
                      );

                    return (
                      <Box
                        key={tenant.id}
                        sx={{
                          display: 'grid',
                          gridTemplateColumns: {
                            xs: '1fr',
                            md: 'minmax(0,1fr) repeat(4,90px)',
                          },
                          alignItems: 'center',
                          gap: {
                            xs: 1.5,
                            md: 1,
                          },
                          p: 1.75,
                          borderRadius: 3,
                          border: '1px solid',
                          borderColor: 'divider',
                          transition:
                            'transform 180ms ease, border-color 180ms ease, background-color 180ms ease',

                          '&:hover': {
                            transform:
                              'translateX(3px)',
                            borderColor:
                              alpha(
                                '#7F56D9',
                                0.2
                              ),
                            backgroundColor:
                              alpha(
                                '#7F56D9',
                                0.025
                              ),
                          },
                        }}
                      >
                        <Box
                          sx={{
                            minWidth: 0,
                            display: 'flex',
                            alignItems: 'center',
                            gap: 1.25,
                          }}
                        >
                          <Box
                            sx={{
                              width: 40,
                              height: 40,
                              flexShrink: 0,
                              display: 'grid',
                              placeItems: 'center',
                              borderRadius: 2.5,
                              color: '#6941C6',
                              backgroundColor:
                                alpha(
                                  '#7F56D9',
                                  0.075
                                ),
                            }}
                          >
                            <ApartmentRounded
                              sx={{
                                fontSize: 20,
                              }}
                            />
                          </Box>

                          <Box sx={{ minWidth: 0 }}>
                            <Box
                              sx={{
                                display: 'flex',
                                alignItems:
                                  'center',
                                flexWrap: 'wrap',
                                gap: 0.75,
                              }}
                            >
                              <Typography
                                sx={{
                                  overflow:
                                    'hidden',
                                  fontSize:
                                    '0.8rem',
                                  fontWeight:
                                    670,
                                  textOverflow:
                                    'ellipsis',
                                  whiteSpace:
                                    'nowrap',
                                }}
                              >
                                {tenant.name}
                              </Typography>

                              <Chip
                                label={
                                  tenant.is_active
                                    ? 'Active'
                                    : 'Suspended'
                                }
                                size="small"
                                color={
                                  tenant.is_active
                                    ? 'success'
                                    : 'error'
                                }
                                sx={{
                                  height: 23,
                                  fontSize:
                                    '0.6rem',
                                }}
                              />
                            </Box>

                            <Typography
                              sx={{
                                mt: 0.25,
                                color:
                                  'text.secondary',
                                fontSize:
                                  '0.65rem',
                              }}
                            >
                              {tenant.domain} ·{' '}
                              {activityTotal.toLocaleString()}{' '}
                              total records
                            </Typography>
                          </Box>
                        </Box>

                        {[
                          {
                            label: 'Users',
                            value:
                              tenantStats.users,
                          },
                          {
                            label: 'Patients',
                            value:
                              tenantStats.patients,
                          },
                          {
                            label: 'Tests',
                            value:
                              tenantStats.lab_tests,
                          },
                          {
                            label: 'Results',
                            value:
                              tenantStats.test_results,
                          },
                        ].map((item) => (
                          <Box
                            key={item.label}
                            sx={{
                              textAlign: {
                                xs: 'left',
                                md: 'center',
                              },
                            }}
                          >
                            <Typography
                              sx={{
                                fontSize:
                                  '0.84rem',
                                fontWeight: 690,
                              }}
                            >
                              {safeNumber(
                                item.value
                              ).toLocaleString()}
                            </Typography>

                            <Typography
                              sx={{
                                mt: 0.15,
                                color:
                                  'text.secondary',
                                fontSize:
                                  '0.59rem',
                              }}
                            >
                              {item.label}
                            </Typography>
                          </Box>
                        ))}
                      </Box>
                    );
                  }
                )}
              </Box>
            )}
          </CardContent>
        </Card>

        <Box
          sx={{
            display: 'grid',
            gap: 2.5,
          }}
        >
          <Card
            sx={{
              background:
                'linear-gradient(145deg, rgba(127,86,217,0.06), rgba(8,145,178,0.025))',
            }}
          >
            <CardContent>
              <Typography
                variant="overline"
                sx={{
                  color: '#6941C6',
                }}
              >
                Platform command
              </Typography>

              <Typography
                component="h3"
                sx={{
                  mt: 0.6,
                  fontSize: '1.18rem',
                  fontWeight: 700,
                  letterSpacing: '-0.02em',
                }}
              >
                Administrative control
              </Typography>

              <Typography
                sx={{
                  mt: 0.75,
                  color: 'text.secondary',
                  fontSize: '0.74rem',
                  lineHeight: 1.6,
                }}
              >
                Manage tenant configuration and
                user access from the Rezhel landlord
                environment.
              </Typography>

              <Box
                sx={{
                  display: 'grid',
                  gap: 1,
                  mt: 2.5,
                }}
              >
                <Button
                  fullWidth
                  variant="outlined"
                  startIcon={<ApartmentRounded />}
                  endIcon={
                    <ArrowForwardRounded />
                  }
                  onClick={() => {
                    navigate(
                      '/landlord/tenants'
                    );
                  }}
                  sx={{
                    minHeight: 52,
                    justifyContent:
                      'flex-start',

                    '& .MuiButton-endIcon': {
                      ml: 'auto',
                    },
                  }}
                >
                  Manage laboratories
                </Button>

                <Button
                  fullWidth
                  variant="outlined"
                  startIcon={<GroupsRounded />}
                  endIcon={
                    <ArrowForwardRounded />
                  }
                  onClick={() => {
                    navigate(
                      '/landlord/users'
                    );
                  }}
                  sx={{
                    minHeight: 52,
                    justifyContent:
                      'flex-start',

                    '& .MuiButton-endIcon': {
                      ml: 'auto',
                    },
                  }}
                >
                  Manage platform users
                </Button>
              </Box>
            </CardContent>
          </Card>

          <Card>
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
                    width: 43,
                    height: 43,
                    display: 'grid',
                    placeItems: 'center',
                    borderRadius: 2.7,
                    color: 'success.main',
                    backgroundColor:
                      alpha(
                        '#079455',
                        0.075
                      ),
                  }}
                >
                  <CheckCircleRounded />
                </Box>

                <Box>
                  <Typography
                    sx={{
                      fontSize: '0.8rem',
                      fontWeight: 680,
                    }}
                  >
                    Platform access healthy
                  </Typography>

                  <Typography
                    sx={{
                      mt: 0.25,
                      color: 'text.secondary',
                      fontSize: '0.66rem',
                    }}
                  >
                    Landlord authentication active
                  </Typography>
                </Box>
              </Box>

              <Box
                sx={{
                  display: 'grid',
                  gridTemplateColumns:
                    'repeat(2, minmax(0,1fr))',
                  gap: 1,
                  mt: 2.25,
                }}
              >
                <Box
                  sx={{
                    p: 1.5,
                    borderRadius: 2.5,
                    backgroundColor:
                      alpha(
                        '#0B1220',
                        0.025
                      ),
                  }}
                >
                  <Typography
                    sx={{
                      color: 'text.secondary',
                      fontSize: '0.61rem',
                      fontWeight: 660,
                      letterSpacing: '0.05em',
                      textTransform: 'uppercase',
                    }}
                  >
                    Active
                  </Typography>

                  <Typography
                    sx={{
                      mt: 0.7,
                      fontSize: '1.25rem',
                      fontWeight: 730,
                    }}
                  >
                    {activeTenants.length}
                  </Typography>
                </Box>

                <Box
                  sx={{
                    p: 1.5,
                    borderRadius: 2.5,
                    backgroundColor:
                      alpha(
                        '#0B1220',
                        0.025
                      ),
                  }}
                >
                  <Typography
                    sx={{
                      color: 'text.secondary',
                      fontSize: '0.61rem',
                      fontWeight: 660,
                      letterSpacing: '0.05em',
                      textTransform: 'uppercase',
                    }}
                  >
                    Suspended
                  </Typography>

                  <Typography
                    sx={{
                      mt: 0.7,
                      fontSize: '1.25rem',
                      fontWeight: 730,
                    }}
                  >
                    {suspendedTenants}
                  </Typography>
                </Box>
              </Box>
            </CardContent>
          </Card>
        </Box>
      </Box>
    </Box>
  );
};

export default DashboardStats;