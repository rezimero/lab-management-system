import {
  ReactNode,
} from 'react';

import AnalyticsRounded from '@mui/icons-material/AnalyticsRounded';
import BiotechRounded from '@mui/icons-material/BiotechRounded';
import CheckCircleRounded from '@mui/icons-material/CheckCircleRounded';
import HealthAndSafetyRounded from '@mui/icons-material/HealthAndSafetyRounded';
import LockRounded from '@mui/icons-material/LockRounded';

import {
  Box,
  Chip,
  Typography,
} from '@mui/material';

import {
  alpha,
} from '@mui/material/styles';

import {
  motion,
} from 'framer-motion';

import LabBrand from '../brand/LabBrand';

interface AuthShellProps {
  children: ReactNode;
  eyebrow: string;
  title: string;
  description: string;
}

const assuranceItems = [
  {
    icon: <BiotechRounded />,
    title: 'End-to-end laboratory workflow',
    description:
      'From patient registration and specimen handling to approved clinical reporting.',
  },
  {
    icon: <HealthAndSafetyRounded />,
    title: 'Precision built into every step',
    description:
      'Role-aware processes, traceable decisions and controlled result approval.',
  },
  {
    icon: <AnalyticsRounded />,
    title: 'Operational clarity in real time',
    description:
      'A unified view of laboratory activity, workload and critical actions.',
  },
];

const AuthShell = ({
  children,
  eyebrow,
  title,
  description,
}: AuthShellProps) => {
  return (
    <Box
      sx={{
        position: 'relative',
        minHeight: '100vh',
        display: 'grid',
        gridTemplateColumns: {
          xs: 'minmax(0, 1fr)',
          lg: 'minmax(520px, 1.12fr) minmax(520px, 0.88fr)',
        },
        overflow: 'hidden',
        backgroundColor: '#F6F8FC',
      }}
    >
      <Box
        component="section"
        sx={{
          position: 'relative',
          display: {
            xs: 'none',
            lg: 'flex',
          },
          minHeight: '100vh',
          flexDirection: 'column',
          overflow: 'hidden',
          px: {
            lg: 7,
            xl: 10,
          },
          py: {
            lg: 6,
            xl: 7,
          },
          color: '#FFFFFF',
          background:
            'linear-gradient(145deg, #07111F 0%, #0A1729 45%, #0A1D30 100%)',
        }}
      >
        <Box
          aria-hidden="true"
          sx={{
            position: 'absolute',
            inset: 0,
            pointerEvents: 'none',
            opacity: 0.72,
            backgroundImage: `
              linear-gradient(
                rgba(255, 255, 255, 0.025) 1px,
                transparent 1px
              ),
              linear-gradient(
                90deg,
                rgba(255, 255, 255, 0.025) 1px,
                transparent 1px
              )
            `,
            backgroundSize: '42px 42px',
            maskImage:
              'linear-gradient(to bottom, black, transparent 88%)',
          }}
        />

        <Box
          aria-hidden="true"
          sx={{
            position: 'absolute',
            top: '-18%',
            right: '-16%',
            width: 560,
            height: 560,
            borderRadius: '50%',
            background:
              'radial-gradient(circle, rgba(37, 99, 235, 0.30), rgba(37, 99, 235, 0) 68%)',
            filter: 'blur(5px)',
          }}
        />

        <Box
          aria-hidden="true"
          sx={{
            position: 'absolute',
            bottom: '-28%',
            left: '-20%',
            width: 620,
            height: 620,
            borderRadius: '50%',
            background:
              'radial-gradient(circle, rgba(8, 145, 178, 0.26), rgba(8, 145, 178, 0) 67%)',
            filter: 'blur(8px)',
          }}
        />

        <Box
          aria-hidden="true"
          sx={{
            position: 'absolute',
            top: '24%',
            right: '8%',
            width: 280,
            height: 280,
            borderRadius: '50%',
            border:
              '1px solid rgba(255, 255, 255, 0.055)',
            boxShadow: `
              0 0 0 44px rgba(255, 255, 255, 0.018),
              0 0 0 88px rgba(255, 255, 255, 0.012)
            `,
          }}
        />

        <Box
          sx={{
            position: 'relative',
            zIndex: 1,
          }}
        >
          <LabBrand inverse />
        </Box>

        <Box
          sx={{
            position: 'relative',
            zIndex: 1,
            flex: 1,
            display: 'flex',
            flexDirection: 'column',
            justifyContent: 'center',
            maxWidth: 700,
            py: 7,
          }}
        >
          <motion.div
            initial={{
              opacity: 0,
              y: 24,
            }}
            animate={{
              opacity: 1,
              y: 0,
            }}
            transition={{
              duration: 0.72,
              ease: [0.22, 1, 0.36, 1],
            }}
          >
            <Chip
              icon={
                <CheckCircleRounded
                  sx={{
                    fontSize: '17px !important',
                  }}
                />
              }
              label="Enterprise laboratory operations"
              sx={{
                mb: 3.25,
                height: 34,
                px: 0.5,
                color: '#A5F3FC',
                border:
                  '1px solid rgba(103, 232, 249, 0.16)',
                backgroundColor:
                  'rgba(8, 145, 178, 0.10)',

                '& .MuiChip-icon': {
                  color: '#67E8F9',
                },
              }}
            />

            <Typography
              component="p"
              sx={{
                maxWidth: 680,
                color: '#FFFFFF',
                fontSize: {
                  lg: '3rem',
                  xl: '3.65rem',
                },
                lineHeight: 1.04,
                fontWeight: 750,
                letterSpacing: '-0.052em',
              }}
            >
              Clarity for every sample.
              <Box
                component="span"
                sx={{
                  display: 'block',
                  mt: 0.5,
                  color: '#8EDDF0',
                }}
              >
                Confidence in every result.
              </Box>
            </Typography>

            <Typography
              sx={{
                maxWidth: 610,
                mt: 3,
                color:
                  'rgba(255, 255, 255, 0.64)',
                fontSize: '1rem',
                lineHeight: 1.8,
                fontWeight: 420,
              }}
            >
              A precision workspace for modern
              laboratories—bringing people, specimens,
              clinical workflows and reporting together
              in one secure operating system.
            </Typography>
          </motion.div>

          <Box
            sx={{
              display: 'grid',
              gridTemplateColumns: {
                lg: 'repeat(3, minmax(0, 1fr))',
              },
              gap: 1.5,
              mt: 6,
            }}
          >
            {assuranceItems.map((item, index) => (
              <motion.div
                key={item.title}
                initial={{
                  opacity: 0,
                  y: 18,
                }}
                animate={{
                  opacity: 1,
                  y: 0,
                }}
                transition={{
                  delay: 0.16 + index * 0.09,
                  duration: 0.56,
                  ease: [0.22, 1, 0.36, 1],
                }}
              >
                <Box
                  sx={{
                    height: '100%',
                    p: 2.25,
                    borderRadius: 4,
                    border:
                      '1px solid rgba(255, 255, 255, 0.07)',
                    background:
                      'linear-gradient(145deg, rgba(255,255,255,0.065), rgba(255,255,255,0.025))',
                    backdropFilter: 'blur(16px)',
                    transition:
                      'transform 220ms ease, border-color 220ms ease, background-color 220ms ease',

                    '&:hover': {
                      transform: 'translateY(-3px)',
                      borderColor:
                        'rgba(103, 232, 249, 0.18)',
                      background:
                        'linear-gradient(145deg, rgba(255,255,255,0.085), rgba(255,255,255,0.035))',
                    },
                  }}
                >
                  <Box
                    sx={{
                      width: 40,
                      height: 40,
                      display: 'grid',
                      placeItems: 'center',
                      mb: 2,
                      borderRadius: 2.5,
                      color: '#A5F3FC',
                      backgroundColor:
                        'rgba(8, 145, 178, 0.12)',

                      '& svg': {
                        fontSize: 21,
                      },
                    }}
                  >
                    {item.icon}
                  </Box>

                  <Typography
                    sx={{
                      color: '#FFFFFF',
                      fontSize: '0.84rem',
                      lineHeight: 1.45,
                      fontWeight: 670,
                    }}
                  >
                    {item.title}
                  </Typography>

                  <Typography
                    sx={{
                      mt: 0.8,
                      color:
                        'rgba(255, 255, 255, 0.48)',
                      fontSize: '0.72rem',
                      lineHeight: 1.62,
                    }}
                  >
                    {item.description}
                  </Typography>
                </Box>
              </motion.div>
            ))}
          </Box>
        </Box>

        <Box
          sx={{
            position: 'relative',
            zIndex: 1,
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'space-between',
            gap: 3,
            pt: 3,
            borderTop:
              '1px solid rgba(255, 255, 255, 0.07)',
          }}
        >
          <Box
            sx={{
              display: 'flex',
              alignItems: 'center',
              gap: 1,
            }}
          >
            <Box
              sx={{
                position: 'relative',
                width: 9,
                height: 9,
                borderRadius: '50%',
                backgroundColor: '#32D583',
                boxShadow:
                  '0 0 0 5px rgba(50, 213, 131, 0.10)',

                '&::after': {
                  content: '""',
                  position: 'absolute',
                  inset: -5,
                  borderRadius: '50%',
                  border:
                    '1px solid rgba(50, 213, 131, 0.26)',
                  animation:
                    'systemPulse 2.2s ease-out infinite',
                },

                '@keyframes systemPulse': {
                  '0%': {
                    transform: 'scale(0.7)',
                    opacity: 0.9,
                  },
                  '100%': {
                    transform: 'scale(1.8)',
                    opacity: 0,
                  },
                },
              }}
            />

            <Typography
              sx={{
                color:
                  'rgba(255, 255, 255, 0.58)',
                fontSize: '0.73rem',
                fontWeight: 560,
              }}
            >
              Secure workspace ready
            </Typography>
          </Box>

          <Box
            sx={{
              display: 'flex',
              alignItems: 'center',
              gap: 0.8,
              color:
                'rgba(255, 255, 255, 0.42)',
            }}
          >
            <LockRounded sx={{ fontSize: 15 }} />

            <Typography
              sx={{
                fontSize: '0.7rem',
                fontWeight: 540,
              }}
            >
              Protected access
            </Typography>
          </Box>
        </Box>
      </Box>

      <Box
        component="main"
        sx={{
          position: 'relative',
          minHeight: '100vh',
          display: 'flex',
          flexDirection: 'column',
          overflowY: 'auto',
          px: {
            xs: 2.25,
            sm: 4,
            md: 7,
            xl: 9,
          },
          py: {
            xs: 2.5,
            sm: 4,
            lg: 5,
          },
        }}
      >
        <Box
          aria-hidden="true"
          sx={{
            position: 'fixed',
            top: '-140px',
            right: '-120px',
            width: 390,
            height: 390,
            borderRadius: '50%',
            pointerEvents: 'none',
            background:
              'radial-gradient(circle, rgba(36, 107, 253, 0.09), rgba(36, 107, 253, 0) 70%)',
          }}
        />

        <Box
          sx={{
            display: {
              xs: 'block',
              lg: 'none',
            },
            mb: {
              xs: 5,
              sm: 6,
            },
          }}
        >
          <LabBrand compact />
        </Box>

        <Box
          sx={{
            width: '100%',
            maxWidth: 520,
            m: 'auto',
            py: {
              xs: 1,
              sm: 3,
              lg: 5,
            },
          }}
        >
          <motion.div
            initial={{
              opacity: 0,
              x: 24,
            }}
            animate={{
              opacity: 1,
              x: 0,
            }}
            transition={{
              duration: 0.64,
              ease: [0.22, 1, 0.36, 1],
            }}
          >
            <Typography
              variant="overline"
              sx={{
                color: 'primary.main',
              }}
            >
              {eyebrow}
            </Typography>

            <Typography
              component="h1"
              sx={{
                mt: 1.25,
                color: 'text.primary',
                fontSize: {
                  xs: '2.2rem',
                  sm: '2.65rem',
                },
                lineHeight: 1.08,
                fontWeight: 750,
                letterSpacing: '-0.046em',
              }}
            >
              {title}
            </Typography>

            <Typography
              sx={{
                maxWidth: 470,
                mt: 1.75,
                color: 'text.secondary',
                fontSize: {
                  xs: '0.9rem',
                  sm: '0.96rem',
                },
                lineHeight: 1.72,
              }}
            >
              {description}
            </Typography>

            <Box
              sx={{
                mt: {
                  xs: 4,
                  sm: 5,
                },
              }}
            >
              {children}
            </Box>
          </motion.div>
        </Box>

        <Box
          sx={{
            display: 'flex',
            justifyContent: 'center',
            pt: 4,
          }}
        >
          <Typography
            sx={{
              color: alpha('#0B1220', 0.44),
              fontSize: '0.7rem',
              textAlign: 'center',
            }}
          >
            Secure multi-tenant laboratory operations
          </Typography>
        </Box>
      </Box>
    </Box>
  );
};

export default AuthShell;