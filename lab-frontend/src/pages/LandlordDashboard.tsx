import {
  MouseEvent,
  ReactNode,
  useState,
} from 'react';

import AdminPanelSettingsRounded from '@mui/icons-material/AdminPanelSettingsRounded';
import ApartmentRounded from '@mui/icons-material/ApartmentRounded';
import AssignmentTurnedInRounded from '@mui/icons-material/AssignmentTurnedInRounded';
import BiotechRounded from '@mui/icons-material/BiotechRounded';
import DashboardRounded from '@mui/icons-material/DashboardRounded';
import GroupsRounded from '@mui/icons-material/GroupsRounded';
import KeyboardArrowDownRounded from '@mui/icons-material/KeyboardArrowDownRounded';
import LogoutRounded from '@mui/icons-material/LogoutRounded';
import MenuRounded from '@mui/icons-material/MenuRounded';
import PeopleAltRounded from '@mui/icons-material/PeopleAltRounded';
import ShieldRounded from '@mui/icons-material/ShieldRounded';

import {
  AppBar,
  Avatar,
  Box,
  Button,
  Chip,
  Divider,
  Drawer,
  IconButton,
  List,
  ListItemButton,
  ListItemIcon,
  ListItemText,
  Menu,
  MenuItem,
  Toolbar,
  Typography,
  alpha,
  useTheme,
} from '@mui/material';

import {
  Navigate,
  Route,
  Routes,
  useLocation,
  useNavigate,
} from 'react-router-dom';

import AllTenants from '../components/landlords/AllTenants';
import AllUsers from '../components/landlords/AllUsers';
import DashboardStats from '../components/landlords/DashboardStats';
import ImpersonateButton from '../components/landlords/ImpersonateButton';

import LabBrand from '../components/brand/LabBrand';

import {
  useAuth,
} from '../contexts/AuthContext';

interface NavigationItem {
  label: string;
  description: string;
  path: string;
  icon: ReactNode;
}

interface PlatformModulePreviewProps {
  eyebrow: string;
  title: string;
  description: string;
  icon: ReactNode;
}

const drawerWidth = 292;
const topBarHeight = 76;

const navigationItems: NavigationItem[] = [
  {
    label: 'Platform overview',
    description: 'Rezhel operational intelligence',
    path: '/landlord',
    icon: <DashboardRounded />,
  },
  {
    label: 'Tenant management',
    description: 'Laboratories on Rezhel',
    path: '/landlord/tenants',
    icon: <ApartmentRounded />,
  },
  {
    label: 'Platform users',
    description: 'Users across all tenants',
    path: '/landlord/users',
    icon: <GroupsRounded />,
  },
  {
    label: 'Patient intelligence',
    description: 'Cross-tenant patient visibility',
    path: '/landlord/patients',
    icon: <PeopleAltRounded />,
  },
  {
    label: 'Test catalogue',
    description: 'Laboratory test oversight',
    path: '/landlord/lab-tests',
    icon: <BiotechRounded />,
  },
  {
    label: 'Clinical results',
    description: 'Platform result oversight',
    path: '/landlord/test-results',
    icon: <AssignmentTurnedInRounded />,
  },
];

const getInitials = (
  name?: string
): string => {
  if (!name) {
    return 'SA';
  }

  return name
    .trim()
    .split(/\s+/)
    .map((part) => part[0])
    .join('')
    .toUpperCase()
    .slice(0, 2);
};

const PlatformModulePreview = ({
  eyebrow,
  title,
  description,
  icon,
}: PlatformModulePreviewProps) => {
  return (
    <Box
      sx={{
        minHeight: 520,
        display: 'grid',
        placeItems: 'center',
      }}
    >
      <Box
        sx={{
          width: '100%',
          maxWidth: 720,
          p: {
            xs: 3,
            sm: 5,
          },
          textAlign: 'center',
          borderRadius: 5,
          border: '1px solid',
          borderColor: 'divider',
          background:
            'linear-gradient(145deg, rgba(255,255,255,0.96), rgba(247,249,253,0.88))',
          boxShadow:
            '0 24px 70px rgba(11,18,32,0.07)',
        }}
      >
        <Box
          sx={{
            width: 64,
            height: 64,
            display: 'grid',
            placeItems: 'center',
            mx: 'auto',
            mb: 2.5,
            borderRadius: 4,
            color: '#6941C6',
            border:
              '1px solid rgba(127,86,217,0.11)',
            backgroundColor:
              'rgba(127,86,217,0.075)',

            '& svg': {
              fontSize: 31,
            },
          }}
        >
          {icon}
        </Box>

        <Typography
          variant="overline"
          sx={{
            color: '#6941C6',
          }}
        >
          {eyebrow}
        </Typography>

        <Typography
          component="h2"
          sx={{
            mt: 1,
            color: 'text.primary',
            fontSize: {
              xs: '1.75rem',
              sm: '2.2rem',
            },
            lineHeight: 1.12,
            fontWeight: 750,
            letterSpacing: '-0.04em',
          }}
        >
          {title}
        </Typography>

        <Typography
          sx={{
            maxWidth: 550,
            mx: 'auto',
            mt: 1.5,
            color: 'text.secondary',
            fontSize: '0.9rem',
            lineHeight: 1.75,
          }}
        >
          {description}
        </Typography>

        <Chip
          icon={<ShieldRounded />}
          label="Landlord-scoped access"
          sx={{
            mt: 3,
            color: 'success.dark',
            backgroundColor:
              alpha('#079455', 0.075),

            '& .MuiChip-icon': {
              color: 'success.main',
            },
          }}
        />
      </Box>
    </Box>
  );
};

const LandlordDashboard = () => {
  const theme = useTheme();
  const navigate = useNavigate();
  const location = useLocation();

  const {
    user,
    logout,
  } = useAuth();

  const [
    mobileOpen,
    setMobileOpen,
  ] = useState(false);

  const [
    profileAnchor,
    setProfileAnchor,
  ] = useState<HTMLElement | null>(null);

  const activeNavigationItem =
    navigationItems.find((item) => {
      if (item.path === '/landlord') {
        return (
          location.pathname === '/landlord' ||
          location.pathname === '/landlord/'
        );
      }

      return location.pathname.startsWith(
        item.path
      );
    });

  const pageTitle =
    activeNavigationItem?.label ??
    'Platform control';

  const handleNavigation = (
    path: string
  ) => {
    navigate(path);
    setMobileOpen(false);
  };

  const handleProfileOpen = (
    event: MouseEvent<HTMLElement>
  ) => {
    setProfileAnchor(
      event.currentTarget
    );
  };

  const handleProfileClose = () => {
    setProfileAnchor(null);
  };

  const handleLogout =
    async (): Promise<void> => {
      handleProfileClose();

      await logout();

      navigate('/login', {
        replace: true,
      });
    };

  const drawerContent = (
    <Box
      sx={{
        position: 'relative',
        height: '100%',
        display: 'flex',
        flexDirection: 'column',
        overflow: 'hidden',
        color: '#FFFFFF',
        background:
          'linear-gradient(180deg, #07111F 0%, #0A1729 48%, #07131F 100%)',
      }}
    >
      <Box
        aria-hidden="true"
        sx={{
          position: 'absolute',
          top: -160,
          right: -145,
          width: 360,
          height: 360,
          borderRadius: '50%',
          pointerEvents: 'none',
          background:
            'radial-gradient(circle, rgba(127,86,217,0.31), rgba(127,86,217,0) 69%)',
        }}
      />

      <Box
        aria-hidden="true"
        sx={{
          position: 'absolute',
          bottom: -210,
          left: -175,
          width: 410,
          height: 410,
          borderRadius: '50%',
          pointerEvents: 'none',
          background:
            'radial-gradient(circle, rgba(8,145,178,0.22), rgba(8,145,178,0) 68%)',
        }}
      />

      <Box
        aria-hidden="true"
        sx={{
          position: 'absolute',
          inset: 0,
          opacity: 0.35,
          pointerEvents: 'none',
          backgroundImage: `
            linear-gradient(
              rgba(255,255,255,0.025) 1px,
              transparent 1px
            ),
            linear-gradient(
              90deg,
              rgba(255,255,255,0.025) 1px,
              transparent 1px
            )
          `,
          backgroundSize: '38px 38px',
          maskImage:
            'linear-gradient(to bottom, black, transparent 86%)',
        }}
      />

      <Box
        sx={{
          position: 'relative',
          zIndex: 1,
          px: 2.75,
          pt: 3,
          pb: 2.5,
        }}
      >
        <LabBrand
          inverse
          compact
          tenantName="Platform Control"
        />
      </Box>

      <Box
        sx={{
          position: 'relative',
          zIndex: 1,
          mx: 2,
          mb: 2.75,
          p: 1.75,
          borderRadius: 3.4,
          border:
            '1px solid rgba(255,255,255,0.075)',
          background:
            'linear-gradient(145deg, rgba(255,255,255,0.075), rgba(255,255,255,0.025))',
          backdropFilter: 'blur(18px)',
        }}
      >
        <Box
          sx={{
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
              borderRadius: 2.6,
              color: '#DDD6FE',
              border:
                '1px solid rgba(196,181,253,0.1)',
              backgroundColor:
                'rgba(127,86,217,0.14)',
            }}
          >
            <AdminPanelSettingsRounded
              sx={{
                fontSize: 21,
              }}
            />
          </Box>

          <Box
            sx={{
              minWidth: 0,
              flex: 1,
            }}
          >
            <Typography
              sx={{
                color: '#FFFFFF',
                fontSize: '0.78rem',
                fontWeight: 680,
              }}
            >
              Landlord environment
            </Typography>

            <Typography
              sx={{
                mt: 0.25,
                color:
                  'rgba(255,255,255,0.42)',
                fontSize: '0.64rem',
                lineHeight: 1.4,
              }}
            >
              Cross-tenant platform administration
            </Typography>
          </Box>

          <Box
            sx={{
              width: 8,
              height: 8,
              flexShrink: 0,
              borderRadius: '50%',
              backgroundColor: '#32D583',
              boxShadow:
                '0 0 0 5px rgba(50,213,131,0.09)',
            }}
          />
        </Box>
      </Box>

      <Box
        component="nav"
        aria-label="Rezhel platform navigation"
        sx={{
          position: 'relative',
          zIndex: 1,
          flex: 1,
          overflowY: 'auto',
          px: 1.5,
          pb: 2,
        }}
      >
        <Typography
          sx={{
            px: 1.5,
            mb: 0.9,
            color:
              'rgba(255,255,255,0.31)',
            fontSize: '0.63rem',
            lineHeight: 1.5,
            fontWeight: 720,
            letterSpacing: '0.095em',
            textTransform: 'uppercase',
          }}
        >
          Platform administration
        </Typography>

        <List
          disablePadding
          sx={{
            display: 'grid',
            gap: 0.45,
          }}
        >
          {navigationItems.map((item) => {
            const selected =
              item.path === '/landlord'
                ? location.pathname ===
                    '/landlord' ||
                  location.pathname ===
                    '/landlord/'
                : location.pathname.startsWith(
                    item.path
                  );

            return (
              <ListItemButton
                key={item.path}
                selected={selected}
                onClick={() => {
                  handleNavigation(
                    item.path
                  );
                }}
                sx={{
                  position: 'relative',
                  minHeight: 57,
                  px: 1.35,
                  borderRadius: 2.8,
                  color: selected
                    ? '#FFFFFF'
                    : 'rgba(255,255,255,0.57)',
                  transition:
                    'transform 180ms ease, color 180ms ease, background-color 180ms ease',

                  '&::before': {
                    content: '""',
                    position: 'absolute',
                    left: 0,
                    top: '25%',
                    width: 3,
                    height: '50%',
                    borderRadius:
                      '0 999px 999px 0',
                    opacity: selected
                      ? 1
                      : 0,
                    background:
                      'linear-gradient(180deg, #A78BFA, #22D3EE)',
                    boxShadow:
                      '0 0 18px rgba(167,139,250,0.5)',
                  },

                  '&.Mui-selected': {
                    background:
                      'linear-gradient(135deg, rgba(127,86,217,0.23), rgba(8,145,178,0.09))',
                    boxShadow:
                      'inset 0 0 0 1px rgba(167,139,250,0.09)',

                    '&:hover': {
                      background:
                        'linear-gradient(135deg, rgba(127,86,217,0.28), rgba(8,145,178,0.12))',
                    },
                  },

                  '&:hover': {
                    color: '#FFFFFF',
                    transform:
                      'translateX(2px)',
                    backgroundColor:
                      'rgba(255,255,255,0.055)',
                  },
                }}
              >
                <ListItemIcon
                  sx={{
                    minWidth: 40,
                    color: selected
                      ? '#C4B5FD'
                      : 'rgba(255,255,255,0.42)',

                    '& svg': {
                      fontSize: 20,
                    },
                  }}
                >
                  {item.icon}
                </ListItemIcon>

                <ListItemText
                  primary={item.label}
                  secondary={item.description}
                  slotProps={{
                    primary: {
                      sx: {
                        fontSize: '0.76rem',
                        lineHeight: 1.35,
                        fontWeight: selected
                          ? 660
                          : 560,
                      },
                    },

                    secondary: {
                      sx: {
                        mt: 0.15,
                        color:
                          'rgba(255,255,255,0.3)',
                        fontSize: '0.6rem',
                        lineHeight: 1.4,
                      },
                    },
                  }}
                />
              </ListItemButton>
            );
          })}
        </List>
      </Box>

      <Box
        sx={{
          position: 'relative',
          zIndex: 1,
          p: 2,
          borderTop:
            '1px solid rgba(255,255,255,0.06)',
        }}
      >
        <Box
          sx={{
            display: 'flex',
            alignItems: 'center',
            gap: 1.25,
            p: 1.25,
            borderRadius: 3,
            backgroundColor:
              'rgba(255,255,255,0.035)',
          }}
        >
          <Avatar
            sx={{
              width: 40,
              height: 40,
              color: '#FFFFFF',
              fontSize: '0.7rem',
              fontWeight: 720,
              background:
                'linear-gradient(145deg, #7F56D9, #0E7490)',
              boxShadow:
                '0 8px 20px rgba(127,86,217,0.2)',
            }}
          >
            {getInitials(user?.name)}
          </Avatar>

          <Box
            sx={{
              minWidth: 0,
              flex: 1,
            }}
          >
            <Typography
              sx={{
                overflow: 'hidden',
                color: '#FFFFFF',
                fontSize: '0.75rem',
                fontWeight: 650,
                textOverflow: 'ellipsis',
                whiteSpace: 'nowrap',
              }}
            >
              {user?.name ??
                'Platform Administrator'}
            </Typography>

            <Typography
              sx={{
                mt: 0.2,
                color:
                  'rgba(255,255,255,0.38)',
                fontSize: '0.63rem',
              }}
            >
              Rezhel Superadmin
            </Typography>
          </Box>
        </Box>

        <Button
          fullWidth
          startIcon={<LogoutRounded />}
          onClick={() => {
            void handleLogout();
          }}
          sx={{
            mt: 1,
            minHeight: 40,
            justifyContent: 'flex-start',
            color:
              'rgba(255,255,255,0.48)',
            fontSize: '0.73rem',

            '&:hover': {
              color: '#FDA29B',
              backgroundColor:
                'rgba(217,45,32,0.09)',
            },
          }}
        >
          Sign out securely
        </Button>
      </Box>
    </Box>
  );

  return (
    <Box
      sx={{
        minHeight: '100vh',
        backgroundColor:
          'background.default',
      }}
    >
      <AppBar
        position="fixed"
        color="transparent"
        sx={{
          width: {
            xs: '100%',
            md: `calc(100% - ${drawerWidth}px)`,
          },
          ml: {
            xs: 0,
            md: `${drawerWidth}px`,
          },
          zIndex:
            theme.zIndex.drawer - 1,
          borderBottom: '1px solid',
          borderColor: 'divider',
          backgroundColor:
            alpha('#FFFFFF', 0.9),
          backdropFilter:
            'blur(18px) saturate(150%)',
          boxShadow:
            '0 1px 2px rgba(11,18,32,0.025)',
        }}
      >
        <Toolbar
          sx={{
            minHeight:
              `${topBarHeight}px !important`,
            px: {
              xs: 1.5,
              sm: 2.5,
              lg: 4,
            },
          }}
        >
          <IconButton
            edge="start"
            aria-label="Open platform navigation"
            onClick={() => {
              setMobileOpen(true);
            }}
            sx={{
              display: {
                xs: 'inline-flex',
                md: 'none',
              },
              mr: 1,
            }}
          >
            <MenuRounded />
          </IconButton>

          <Box
            sx={{
              minWidth: 0,
              flex: 1,
            }}
          >
            <Typography
              sx={{
                color: 'text.secondary',
                fontSize: '0.66rem',
                fontWeight: 680,
                letterSpacing: '0.075em',
                textTransform: 'uppercase',
              }}
            >
              Rezhel platform
            </Typography>

            <Typography
              component="h1"
              sx={{
                mt: 0.2,
                overflow: 'hidden',
                color: 'text.primary',
                fontSize: {
                  xs: '0.98rem',
                  sm: '1.08rem',
                },
                lineHeight: 1.3,
                fontWeight: 710,
                letterSpacing: '-0.018em',
                textOverflow: 'ellipsis',
                whiteSpace: 'nowrap',
              }}
            >
              {pageTitle}
            </Typography>
          </Box>

          <Box
            sx={{
              display: 'flex',
              alignItems: 'center',
              gap: {
                xs: 0.65,
                sm: 1,
              },
            }}
          >
            <Box
              sx={{
                display: {
                  xs: 'none',
                  md: 'block',
                },
              }}
            >
              <ImpersonateButton />
            </Box>

            <Chip
              icon={<ShieldRounded />}
              label="Secure"
              size="small"
              sx={{
                display: {
                  xs: 'none',
                  sm: 'inline-flex',
                },
                color: 'success.dark',
                backgroundColor:
                  alpha('#079455', 0.075),

                '& .MuiChip-icon': {
                  color: 'success.main',
                },
              }}
            />

            <Chip
              label="Superadmin"
              size="small"
              sx={{
                display: {
                  xs: 'none',
                  lg: 'inline-flex',
                },
                color: '#6941C6',
                backgroundColor:
                  alpha('#7F56D9', 0.08),
              }}
            />

            <Button
              aria-haspopup="menu"
              aria-expanded={
                Boolean(profileAnchor)
                  ? 'true'
                  : undefined
              }
              onClick={handleProfileOpen}
              endIcon={
                <KeyboardArrowDownRounded />
              }
              sx={{
                minWidth: 0,
                minHeight: 48,
                p: 0.55,
                pr: {
                  xs: 0.3,
                  sm: 1,
                },
                color: 'text.primary',
                border: '1px solid',
                borderColor: 'divider',
                backgroundColor:
                  'background.paper',

                '&:hover': {
                  borderColor:
                    alpha('#7F56D9', 0.2),
                  backgroundColor:
                    alpha('#7F56D9', 0.025),
                },
              }}
            >
              <Avatar
                sx={{
                  width: 35,
                  height: 35,
                  color: '#FFFFFF',
                  fontSize: '0.68rem',
                  fontWeight: 720,
                  background:
                    'linear-gradient(145deg, #7F56D9, #0E7490)',
                }}
              >
                {getInitials(user?.name)}
              </Avatar>

              <Box
                sx={{
                  display: {
                    xs: 'none',
                    xl: 'block',
                  },
                  maxWidth: 155,
                  ml: 1,
                  textAlign: 'left',
                }}
              >
                <Typography
                  sx={{
                    overflow: 'hidden',
                    fontSize: '0.72rem',
                    fontWeight: 650,
                    textOverflow: 'ellipsis',
                    whiteSpace: 'nowrap',
                  }}
                >
                  {user?.name}
                </Typography>

                <Typography
                  sx={{
                    mt: 0.1,
                    color: 'text.secondary',
                    fontSize: '0.61rem',
                  }}
                >
                  Rezhel Platform
                </Typography>
              </Box>
            </Button>

            <Menu
              anchorEl={profileAnchor}
              open={Boolean(profileAnchor)}
              onClose={handleProfileClose}
              anchorOrigin={{
                vertical: 'bottom',
                horizontal: 'right',
              }}
              transformOrigin={{
                vertical: 'top',
                horizontal: 'right',
              }}
              slotProps={{
                paper: {
                  sx: {
                    width: 270,
                    mt: 1,
                    overflow: 'hidden',
                  },
                },
              }}
            >
              <Box
                sx={{
                  p: 2,
                  background:
                    'linear-gradient(145deg, rgba(127,86,217,0.07), rgba(8,145,178,0.025))',
                }}
              >
                <Typography
                  sx={{
                    fontSize: '0.82rem',
                    fontWeight: 680,
                  }}
                >
                  {user?.name}
                </Typography>

                <Typography
                  sx={{
                    mt: 0.35,
                    overflow: 'hidden',
                    color: 'text.secondary',
                    fontSize: '0.7rem',
                    textOverflow: 'ellipsis',
                    whiteSpace: 'nowrap',
                  }}
                >
                  {user?.email}
                </Typography>

                <Chip
                  label="Rezhel Superadmin"
                  size="small"
                  sx={{
                    mt: 1.25,
                    color: '#6941C6',
                    backgroundColor:
                      alpha('#7F56D9', 0.08),
                  }}
                />
              </Box>

              <Divider />

              <MenuItem
                onClick={() => {
                  handleProfileClose();
                  navigate('/landlord');
                }}
              >
                <AdminPanelSettingsRounded
                  sx={{
                    mr: 1.25,
                    color: 'text.secondary',
                    fontSize: 20,
                  }}
                />
                Platform overview
              </MenuItem>

              <MenuItem
                onClick={() => {
                  void handleLogout();
                }}
                sx={{
                  color: 'error.main',
                }}
              >
                <LogoutRounded
                  sx={{
                    mr: 1.25,
                    fontSize: 20,
                  }}
                />
                Sign out
              </MenuItem>
            </Menu>
          </Box>
        </Toolbar>
      </AppBar>

      <Box
        component="nav"
        aria-label="Rezhel platform navigation"
      >
        <Drawer
          variant="temporary"
          open={mobileOpen}
          onClose={() => {
            setMobileOpen(false);
          }}
          ModalProps={{
            keepMounted: true,
          }}
          sx={{
            display: {
              xs: 'block',
              md: 'none',
            },

            '& .MuiDrawer-paper': {
              width: drawerWidth,
              border: 0,
            },
          }}
        >
          {drawerContent}
        </Drawer>

        <Drawer
          variant="permanent"
          open
          sx={{
            display: {
              xs: 'none',
              md: 'block',
            },

            '& .MuiDrawer-paper': {
              width: drawerWidth,
              border: 0,
              boxSizing: 'border-box',
              boxShadow:
                '12px 0 42px rgba(7,17,31,0.08)',
            },
          }}
        >
          {drawerContent}
        </Drawer>
      </Box>

      <Box
        component="main"
        sx={{
          minHeight: '100vh',
          ml: {
            xs: 0,
            md: `${drawerWidth}px`,
          },
          pt: `${topBarHeight}px`,
        }}
      >
        <Box
          sx={{
            display: {
              xs: 'block',
              md: 'none',
            },
            px: 2,
            pt: 2,
          }}
        >
          <ImpersonateButton />
        </Box>

        <Box
          sx={{
            width: '100%',
            maxWidth: 1680,
            mx: 'auto',
            p: {
              xs: 2,
              sm: 3,
              lg: 4,
            },
          }}
        >
          <Routes>
            <Route
              index
              element={<DashboardStats />}
            />

            <Route
              path="tenants"
              element={<AllTenants />}
            />

            <Route
              path="users"
              element={<AllUsers />}
            />

            <Route
              path="patients"
              element={
                <PlatformModulePreview
                  eyebrow="Patient intelligence"
                  title="Cross-tenant patient oversight"
                  description="A dedicated landlord-level patient intelligence workspace will consolidate authorised platform-wide visibility without weakening tenant isolation."
                  icon={<PeopleAltRounded />}
                />
              }
            />

            <Route
              path="lab-tests"
              element={
                <PlatformModulePreview
                  eyebrow="Catalogue intelligence"
                  title="Platform test-catalogue oversight"
                  description="Review and govern laboratory test catalogues across Rezhel tenants from a controlled landlord-scoped workspace."
                  icon={<BiotechRounded />}
                />
              }
            />

            <Route
              path="test-results"
              element={
                <PlatformModulePreview
                  eyebrow="Clinical governance"
                  title="Platform result oversight"
                  description="A secure cross-tenant results workspace will provide operational supervision while preserving laboratory ownership and audit boundaries."
                  icon={
                    <AssignmentTurnedInRounded />
                  }
                />
              }
            />

            <Route
              path="*"
              element={
                <Navigate
                  to="/landlord"
                  replace
                />
              }
            />
          </Routes>
        </Box>
      </Box>
    </Box>
  );
};

export default LandlordDashboard;