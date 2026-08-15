import {
  useMemo,
  useState,
} from 'react';

import type {
  ReactNode,
} from 'react';

import AssessmentRounded from
  '@mui/icons-material/AssessmentRounded';
import DashboardRounded from
  '@mui/icons-material/DashboardRounded';
import LogoutRounded from
  '@mui/icons-material/LogoutRounded';
import MenuRounded from
  '@mui/icons-material/MenuRounded';
import ScienceRounded from
  '@mui/icons-material/ScienceRounded';
import AssignmentRounded from
  '@mui/icons-material/AssignmentRounded';
import NotificationsRounded from
  '@mui/icons-material/NotificationsRounded';

import {
  AppBar,
  Avatar,
  Box,
  Button,
  Container,
  Divider,
  IconButton,
  Menu,
  MenuItem,
  Stack,
  Toolbar,
  Tooltip,
  Typography,
} from '@mui/material';

import {
  NavLink,
  Outlet,
  useLocation,
} from 'react-router-dom';

import {
  usePatientPortalAuth,
} from '../context/PatientPortalAuthContext';

import {
  patientDisplayName,
} from '../utils/patientPortalDisplay';

interface NavItem {
  label: string;
  path: string;
  icon: ReactNode;
}

const navItems: NavItem[] = [
  {
    label: 'Dashboard',
    path: '/patient-portal',
    icon: <DashboardRounded fontSize="small" />,
  },
  {
    label: 'Orders',
    path: '/patient-portal/orders',
    icon: <AssignmentRounded fontSize="small" />,
  },
  {
    label: 'Reports',
    path: '/patient-portal/reports',
    icon: <AssessmentRounded fontSize="small" />,
  },
  {
    label: 'Notifications',
    path: '/patient-portal/notifications',
    icon: <NotificationsRounded fontSize="small" />,
  },
];

const PatientPortalShell = () => {
  const auth = usePatientPortalAuth();
  const location = useLocation();

  const [
    mobileAnchor,
    setMobileAnchor,
  ] = useState<null | HTMLElement>(null);

  const [
    accountAnchor,
    setAccountAnchor,
  ] = useState<null | HTMLElement>(null);

  const displayName = useMemo(
    () => patientDisplayName(
      auth.session?.patient.first_name,
      auth.session?.patient.last_name,
      auth.session?.patient.full_name
    ),
    [auth.session]
  );

  const initials = useMemo(() => {
    const parts = displayName
      .split(/\s+/)
      .filter(Boolean)
      .slice(0, 2);

    return parts
      .map((part) => part[0]?.toUpperCase())
      .join('')
      || 'P';
  }, [displayName]);

  const isActive = (
    path: string
  ): boolean => (
    path === '/patient-portal'
      ? location.pathname === path
      : location.pathname.startsWith(path)
  );

  const handleLogout =
    async (): Promise<void> => {
      setAccountAnchor(null);
      await auth.logout();
    };

  return (
    <Box
      sx={{
        minHeight: '100vh',
        backgroundColor: '#F7F9FC',
      }}
    >
      <AppBar
        position="sticky"
        color="inherit"
        sx={{
          borderBottom: '1px solid',
          borderColor: 'divider',
          backgroundColor:
            'rgba(255,255,255,0.94)',
          backdropFilter: 'blur(18px)',
        }}
      >
        <Container maxWidth="xl">
          <Toolbar
            disableGutters
            sx={{
              minHeight: {
                xs: 68,
                md: 74,
              },
              gap: 2,
            }}
          >
            <Box
              component={NavLink}
              to="/patient-portal"
              aria-label="Patient Portal home"
              sx={{
                display: 'inline-flex',
                alignItems: 'center',
                gap: 1.25,
                color: 'inherit',
                textDecoration: 'none',
              }}
            >
              <Box
                sx={{
                  width: 38,
                  height: 38,
                  display: 'grid',
                  placeItems: 'center',
                  borderRadius: '12px',
                  color: 'common.white',
                  background:
                    'linear-gradient(135deg, #246BFD 0%, #1747A6 100%)',
                  boxShadow:
                    '0 8px 20px rgba(36,107,253,0.22)',
                }}
              >
                <ScienceRounded
                  sx={{ fontSize: 22 }}
                />
              </Box>

              <Box>
                <Typography
                  sx={{
                    fontSize: '0.95rem',
                    fontWeight: 760,
                    lineHeight: 1.15,
                  }}
                >
                  Patient Portal
                </Typography>

                <Typography
                  sx={{
                    mt: 0.2,
                    color: 'text.secondary',
                    fontSize: '0.68rem',
                    lineHeight: 1.2,
                  }}
                >
                  Laboratory results access
                </Typography>
              </Box>
            </Box>

            <Box
              component="nav"
              aria-label="Patient portal navigation"
              sx={{
                ml: {
                  md: 3,
                },
                display: {
                  xs: 'none',
                  md: 'flex',
                },
                gap: 0.75,
              }}
            >
              {navItems.map((item) => (
                <Button
                  key={item.path}
                  component={NavLink}
                  to={item.path}
                  startIcon={item.icon}
                  sx={{
                    minHeight: 40,
                    px: 1.6,
                    color: isActive(item.path)
                      ? 'primary.main'
                      : 'text.secondary',
                    backgroundColor:
                      isActive(item.path)
                        ? 'rgba(36,107,253,0.08)'
                        : 'transparent',
                  }}
                >
                  {item.label}
                </Button>
              ))}
            </Box>

            <Box sx={{ flex: 1 }} />

            <Tooltip title="Open navigation">
              <IconButton
                aria-label="Open patient portal navigation"
                onClick={(event) => {
                  setMobileAnchor(
                    event.currentTarget
                  );
                }}
                sx={{
                  display: {
                    xs: 'inline-flex',
                    md: 'none',
                  },
                }}
              >
                <MenuRounded />
              </IconButton>
            </Tooltip>

            <Divider
              orientation="vertical"
              flexItem
              sx={{
                my: 2,
                display: {
                  xs: 'none',
                  sm: 'block',
                },
              }}
            />

            <Button
              onClick={(event) => {
                setAccountAnchor(
                  event.currentTarget
                );
              }}
              aria-haspopup="menu"
              aria-expanded={
                Boolean(accountAnchor)
                  ? 'true'
                  : undefined
              }
              sx={{
                minWidth: 0,
                p: 0.5,
                borderRadius: '14px',
                color: 'text.primary',
                textTransform: 'none',
              }}
            >
              <Stack
                direction="row"
                spacing={1}
                sx={{
                  alignItems: 'center',
                }}
              >
                <Avatar
                  sx={{
                    width: 36,
                    height: 36,
                    fontSize: '0.78rem',
                    fontWeight: 760,
                    backgroundColor:
                      'primary.main',
                  }}
                >
                  {initials}
                </Avatar>

                <Box
                  sx={{
                    display: {
                      xs: 'none',
                      sm: 'block',
                    },
                    minWidth: 0,
                    textAlign: 'left',
                  }}
                >
                  <Typography
                    noWrap
                    sx={{
                      maxWidth: 180,
                      fontSize: '0.8rem',
                      fontWeight: 690,
                    }}
                  >
                    {displayName}
                  </Typography>

                  <Typography
                    noWrap
                    sx={{
                      maxWidth: 180,
                      color: 'text.secondary',
                      fontSize: '0.68rem',
                    }}
                  >
                    {auth.session?.patient
                      .medical_record_number
                      || 'Patient account'}
                  </Typography>
                </Box>
              </Stack>
            </Button>

            <Menu
              anchorEl={mobileAnchor}
              open={Boolean(mobileAnchor)}
              onClose={() => {
                setMobileAnchor(null);
              }}
              slotProps={{
                list: {
                  'aria-label':
                    'Patient portal navigation',
                },
              }}
            >
              {navItems.map((item) => (
                <MenuItem
                  key={item.path}
                  component={NavLink}
                  to={item.path}
                  selected={isActive(item.path)}
                  onClick={() => {
                    setMobileAnchor(null);
                  }}
                  sx={{
                    gap: 1.25,
                  }}
                >
                  {item.icon}
                  {item.label}
                </MenuItem>
              ))}
            </Menu>

            <Menu
              anchorEl={accountAnchor}
              open={Boolean(accountAnchor)}
              onClose={() => {
                setAccountAnchor(null);
              }}
              slotProps={{
                list: {
                  'aria-label':
                    'Patient portal account menu',
                },
              }}
            >
              <Box
                sx={{
                  px: 2,
                  py: 1.25,
                  minWidth: 230,
                }}
              >
                <Typography
                  sx={{
                    fontSize: '0.8rem',
                    fontWeight: 700,
                  }}
                >
                  {displayName}
                </Typography>

                <Typography
                  sx={{
                    mt: 0.25,
                    color: 'text.secondary',
                    fontSize: '0.72rem',
                  }}
                >
                  {auth.session?.account.email}
                </Typography>
              </Box>

              <Divider />

              <MenuItem
                onClick={() => {
                  void handleLogout();
                }}
                sx={{
                  gap: 1.25,
                  color: 'error.main',
                }}
              >
                <LogoutRounded fontSize="small" />
                Sign out
              </MenuItem>
            </Menu>
          </Toolbar>
        </Container>
      </AppBar>

      <Container
        component="main"
        maxWidth="xl"
        sx={{
          py: {
            xs: 3,
            md: 4,
          },
        }}
      >
        <Outlet />
      </Container>
    </Box>
  );
};

export default PatientPortalShell;
