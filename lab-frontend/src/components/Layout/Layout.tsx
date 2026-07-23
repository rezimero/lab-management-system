import {
  MouseEvent,
  ReactNode,
  useMemo,
  useState,
} from 'react';

import AccountCircleRounded from '@mui/icons-material/AccountCircleRounded';
import DashboardRounded from '@mui/icons-material/DashboardRounded';
import DomainRounded from '@mui/icons-material/DomainRounded';
import FactCheckRounded from '@mui/icons-material/FactCheckRounded';
import GroupsRounded from '@mui/icons-material/GroupsRounded';
import KeyboardArrowDownRounded from '@mui/icons-material/KeyboardArrowDownRounded';
import LogoutRounded from '@mui/icons-material/LogoutRounded';
import MenuRounded from '@mui/icons-material/MenuRounded';
import PeopleAltRounded from '@mui/icons-material/PeopleAltRounded';
import ScienceRounded from '@mui/icons-material/ScienceRounded';
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
  Tooltip,
  Typography,
  alpha,
  useTheme,
} from '@mui/material';

import {
  useLocation,
  useNavigate,
} from 'react-router-dom';

import LabBrand from '../brand/LabBrand';
import { useAuth } from '../../contexts/AuthContext';

interface LayoutProps {
  children: ReactNode;
  title?: string;
}

interface NavigationItem {
  label: string;
  path: string;
  icon: ReactNode;
}

interface NavigationGroup {
  label: string;
  items: NavigationItem[];
}

const drawerWidth = 292;
const topBarHeight = 76;

const getInitials = (
  name?: string
): string => {
  if (!name) {
    return 'R';
  }

  return name
    .trim()
    .split(/\s+/)
    .map((part) => part[0])
    .join('')
    .toUpperCase()
    .slice(0, 2);
};

const formatRole = (
  role?: string
): string => {
  return (role ?? 'user')
    .replace(/_/g, ' ')
    .replace(/\b\w/g, (character) =>
      character.toUpperCase()
    );
};

const Layout = ({
  children,
  title,
}: LayoutProps) => {
  const theme = useTheme();
  const navigate = useNavigate();
  const location = useLocation();

  const {
    user,
    tenant,
    logout,
  } = useAuth();

  const [mobileOpen, setMobileOpen] =
    useState(false);

  const [
    profileAnchor,
    setProfileAnchor,
  ] = useState<HTMLElement | null>(null);

  const canManageUsers = [
    'admin',
    'superadmin',
  ].includes(user?.role ?? '');

  const navigationGroups =
    useMemo<NavigationGroup[]>(() => {
      const groups: NavigationGroup[] = [
        {
          label: 'Workspace',
          items: [
            {
              label: 'Overview',
              path: '/dashboard',
              icon: <DashboardRounded />,
            },
          ],
        },
        {
          label: 'Laboratory operations',
          items: [
            {
              label: 'Patients',
              path: '/patients',
              icon: <PeopleAltRounded />,
            },
            {
              label: 'Test catalogue',
              path: '/lab-tests',
              icon: <ScienceRounded />,
            },
            {
              label: 'Test results',
              path: '/test-results',
              icon: <FactCheckRounded />,
            },
          ],
        },
      ];

      if (canManageUsers) {
        groups.push({
          label: 'Administration',
          items: [
            {
              label: 'Users and access',
              path: '/users',
              icon: <GroupsRounded />,
            },
          ],
        });
      }

      return groups;
    }, [canManageUsers]);

  const allNavigationItems =
    navigationGroups.flatMap(
      (group) => group.items
    );

  const activeItem =
    allNavigationItems.find((item) => {
      if (item.path === '/dashboard') {
        return (
          location.pathname === item.path
        );
      }

      return location.pathname.startsWith(
        item.path
      );
    });

  const pageTitle =
    title ??
    activeItem?.label ??
    'Workspace';

  const handleNavigation = (
    path: string
  ) => {
    navigate(path);
    setMobileOpen(false);
  };

  const handleProfileOpen = (
    event: MouseEvent<HTMLElement>
  ) => {
    setProfileAnchor(event.currentTarget);
  };

  const handleProfileClose = () => {
    setProfileAnchor(null);
  };

  const handleLogout = async () => {
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
          'linear-gradient(180deg, #07111F 0%, #091727 52%, #08131F 100%)',
      }}
    >
      <Box
        aria-hidden="true"
        sx={{
          position: 'absolute',
          top: -140,
          right: -150,
          width: 330,
          height: 330,
          borderRadius: '50%',
          pointerEvents: 'none',
          background:
            'radial-gradient(circle, rgba(37, 99, 235, 0.26), rgba(37, 99, 235, 0) 68%)',
        }}
      />

      <Box
        aria-hidden="true"
        sx={{
          position: 'absolute',
          bottom: -190,
          left: -160,
          width: 360,
          height: 360,
          borderRadius: '50%',
          pointerEvents: 'none',
          background:
            'radial-gradient(circle, rgba(8, 145, 178, 0.20), rgba(8, 145, 178, 0) 68%)',
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
          tenantName={
            tenant?.name ??
            'Clinical Intelligence'
          }
        />
      </Box>

      <Box
        sx={{
          position: 'relative',
          zIndex: 1,
          mx: 2,
          mb: 2.5,
          p: 1.75,
          borderRadius: 3.25,
          border:
            '1px solid rgba(255, 255, 255, 0.075)',
          background:
            'linear-gradient(145deg, rgba(255,255,255,0.07), rgba(255,255,255,0.025))',
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
              width: 38,
              height: 38,
              flexShrink: 0,
              display: 'grid',
              placeItems: 'center',
              borderRadius: 2.4,
              color: '#A5F3FC',
              backgroundColor:
                'rgba(8, 145, 178, 0.13)',
            }}
          >
            <DomainRounded
              sx={{
                fontSize: 20,
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
                overflow: 'hidden',
                color: '#FFFFFF',
                fontSize: '0.78rem',
                lineHeight: 1.35,
                fontWeight: 660,
                textOverflow: 'ellipsis',
                whiteSpace: 'nowrap',
              }}
            >
              {tenant?.name ??
                'Rezhel Platform'}
            </Typography>

            <Typography
              sx={{
                mt: 0.25,
                overflow: 'hidden',
                color:
                  'rgba(255, 255, 255, 0.42)',
                fontSize: '0.66rem',
                lineHeight: 1.4,
                textOverflow: 'ellipsis',
                whiteSpace: 'nowrap',
              }}
            >
              {tenant?.domain
                ? `${tenant.domain} workspace`
                : 'Platform workspace'}
            </Typography>
          </Box>

          <Tooltip title="Workspace active">
            <Box
              sx={{
                position: 'relative',
                width: 8,
                height: 8,
                flexShrink: 0,
                borderRadius: '50%',
                backgroundColor: '#32D583',
                boxShadow:
                  '0 0 0 5px rgba(50, 213, 131, 0.09)',
              }}
            />
          </Tooltip>
        </Box>
      </Box>

      <Box
        component="nav"
        aria-label="Primary navigation"
        sx={{
          position: 'relative',
          zIndex: 1,
          flex: 1,
          overflowY: 'auto',
          px: 1.5,
          pb: 2,
        }}
      >
        {navigationGroups.map(
          (group, groupIndex) => (
            <Box
              key={group.label}
              sx={{
                mt: groupIndex === 0
                  ? 0
                  : 2.4,
              }}
            >
              <Typography
                sx={{
                  px: 1.5,
                  mb: 0.9,
                  color:
                    'rgba(255, 255, 255, 0.31)',
                  fontSize: '0.63rem',
                  lineHeight: 1.5,
                  fontWeight: 720,
                  letterSpacing: '0.095em',
                  textTransform: 'uppercase',
                }}
              >
                {group.label}
              </Typography>

              <List
                disablePadding
                sx={{
                  display: 'grid',
                  gap: 0.4,
                }}
              >
                {group.items.map((item) => {
                  const selected =
                    item.path === '/dashboard'
                      ? location.pathname ===
                        item.path
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
                        minHeight: 47,
                        px: 1.35,
                        borderRadius: 2.7,
                        color: selected
                          ? '#FFFFFF'
                          : 'rgba(255, 255, 255, 0.57)',
                        transition:
                          'color 180ms ease, background-color 180ms ease, transform 180ms ease',

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
                            'linear-gradient(180deg, #60A5FA, #22D3EE)',
                          boxShadow:
                            '0 0 18px rgba(34, 211, 238, 0.52)',
                          transition:
                            'opacity 180ms ease',
                        },

                        '&.Mui-selected': {
                          background:
                            'linear-gradient(135deg, rgba(59, 130, 246, 0.21), rgba(8, 145, 178, 0.09))',
                          boxShadow:
                            'inset 0 0 0 1px rgba(96, 165, 250, 0.10)',

                          '&:hover': {
                            background:
                              'linear-gradient(135deg, rgba(59, 130, 246, 0.25), rgba(8, 145, 178, 0.12))',
                          },
                        },

                        '&:hover': {
                          color: '#FFFFFF',
                          transform:
                            'translateX(2px)',
                          backgroundColor:
                            'rgba(255, 255, 255, 0.055)',
                        },

                        '& .MuiListItemText-primary': {
                          fontSize: '0.8rem',
                          fontWeight: selected
                            ? 650
                            : 540,
                        },
                      }}
                    >
                      <ListItemIcon
                        sx={{
                          minWidth: 39,
                          color: selected
                            ? '#8EDDF0'
                            : 'rgba(255, 255, 255, 0.42)',

                          '& svg': {
                            fontSize: 20,
                          },
                        }}
                      >
                        {item.icon}
                      </ListItemIcon>

                      <ListItemText
                        primary={item.label}
                      />
                    </ListItemButton>
                  );
                })}
              </List>
            </Box>
          )
        )}
      </Box>

      <Box
        sx={{
          position: 'relative',
          zIndex: 1,
          p: 2,
          pt: 1.5,
          borderTop:
            '1px solid rgba(255, 255, 255, 0.06)',
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
              'rgba(255, 255, 255, 0.035)',
          }}
        >
          <Avatar
            sx={{
              width: 40,
              height: 40,
              flexShrink: 0,
              color: '#FFFFFF',
              fontSize: '0.76rem',
              fontWeight: 720,
              background:
                'linear-gradient(145deg, #2563EB, #0E7490)',
              boxShadow:
                '0 8px 20px rgba(37, 99, 235, 0.20)',
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
                lineHeight: 1.4,
                fontWeight: 640,
                textOverflow: 'ellipsis',
                whiteSpace: 'nowrap',
              }}
            >
              {user?.name ?? 'Rezhel User'}
            </Typography>

            <Typography
              sx={{
                mt: 0.1,
                overflow: 'hidden',
                color:
                  'rgba(255, 255, 255, 0.38)',
                fontSize: '0.64rem',
                textOverflow: 'ellipsis',
                whiteSpace: 'nowrap',
              }}
            >
              {formatRole(user?.role)}
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
              'rgba(255, 255, 255, 0.48)',
            fontSize: '0.73rem',

            '&:hover': {
              color: '#FDA29B',
              backgroundColor:
                'rgba(217, 45, 32, 0.09)',
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
          zIndex: theme.zIndex.drawer - 1,
          borderBottom: '1px solid',
          borderColor: 'divider',
          backgroundColor:
            alpha('#FFFFFF', 0.88),
          backdropFilter:
            'blur(18px) saturate(150%)',
          boxShadow:
            '0 1px 2px rgba(11, 18, 32, 0.025)',
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
            aria-label="Open navigation"
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
                lineHeight: 1.4,
                fontWeight: 680,
                letterSpacing: '0.075em',
                textTransform: 'uppercase',
              }}
            >
              Rezhel workspace
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
                xs: 0.75,
                sm: 1.25,
              },
            }}
          >
            <Chip
              icon={
                <ShieldRounded
                  sx={{
                    fontSize:
                      '16px !important',
                  }}
                />
              }
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
              label={formatRole(user?.role)}
              size="small"
              sx={{
                display: {
                  xs: 'none',
                  sm: 'inline-flex',
                },
                color: 'primary.dark',
                backgroundColor:
                  alpha('#246BFD', 0.075),
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
                pl: {
                  xs: 0.55,
                  sm: 0.75,
                },
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
                    alpha('#246BFD', 0.2),
                  backgroundColor:
                    alpha('#246BFD', 0.025),
                },
              }}
            >
              <Avatar
                sx={{
                  width: 35,
                  height: 35,
                  color: '#FFFFFF',
                  fontSize: '0.7rem',
                  fontWeight: 720,
                  background:
                    'linear-gradient(145deg, #2563EB, #0E7490)',
                }}
              >
                {getInitials(user?.name)}
              </Avatar>

              <Box
                sx={{
                  display: {
                    xs: 'none',
                    lg: 'block',
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
                    lineHeight: 1.35,
                    fontWeight: 650,
                    textOverflow: 'ellipsis',
                    whiteSpace: 'nowrap',
                  }}
                >
                  {user?.name}
                </Typography>

                <Typography
                  sx={{
                    overflow: 'hidden',
                    color: 'text.secondary',
                    fontSize: '0.61rem',
                    lineHeight: 1.35,
                    textOverflow: 'ellipsis',
                    whiteSpace: 'nowrap',
                  }}
                >
                  {tenant?.name ??
                    'Rezhel Platform'}
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
                    'linear-gradient(145deg, rgba(36,107,253,0.06), rgba(8,145,178,0.025))',
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
                  label={formatRole(user?.role)}
                  size="small"
                  sx={{
                    mt: 1.25,
                    color: 'primary.dark',
                    backgroundColor:
                      alpha('#246BFD', 0.075),
                  }}
                />
              </Box>

              <Divider />

              <MenuItem
                onClick={() => {
                  handleProfileClose();
                  navigate('/dashboard');
                }}
              >
                <AccountCircleRounded
                  sx={{
                    mr: 1.25,
                    color: 'text.secondary',
                    fontSize: 20,
                  }}
                />
                Workspace overview
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
        aria-label="Rezhel navigation"
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
                '12px 0 42px rgba(7, 17, 31, 0.08)',
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
          {children}
        </Box>
      </Box>
    </Box>
  );
};

export default Layout;