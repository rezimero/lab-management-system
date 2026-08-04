import {
  type MouseEvent,
  type ReactNode,
  useMemo,
  useState,
} from 'react';

import {
  Box,
} from '@mui/material';

import {
  useLocation,
  useNavigate,
} from 'react-router-dom';

import {
  useAuth,
} from '../../contexts/AuthContext';

import {
  useTenantEntitlements,
} from '../../features/commercial/hooks/useTenantEntitlements';

import {
  drawerWidth,
  topBarHeight,
} from './layoutConstants';

import LayoutSidebar from
  './LayoutSidebar';

import LayoutTopBar from
  './LayoutTopBar';

import {
  buildNavigationGroups,
  pathIsActive,
} from './navigationConfig';

import ProfileMenu from
  './ProfileMenu';

interface Props {
  children: ReactNode;
  title?: string;
}

const Layout = ({
  children,
  title,
}: Props) => {
  const navigate = useNavigate();
  const location = useLocation();
  const auth = useAuth();
  const entitlements = useTenantEntitlements();

  const [
    mobileOpen,
    setMobileOpen,
  ] = useState(false);

  const [
    profileAnchor,
    setProfileAnchor,
  ] = useState<HTMLElement | null>(null);

  const canManageUsers = [
    'admin',
    'superadmin',
  ].includes(auth.user?.role ?? '');

  const groups = useMemo(
    () => buildNavigationGroups(
      canManageUsers,
      auth.user?.role,
      Boolean(
        entitlements.data?.features.inventory?.enabled
      )
    ),
    [
      auth.user?.role,
      canManageUsers,
      entitlements.data?.features.inventory?.enabled,
    ]
  );

  const activeItem = groups
    .flatMap((group) => group.items)
    .find((item) => pathIsActive(
      location.pathname,
      item.path
    ));

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

  const handleLogout = async () => {
    setProfileAnchor(null);
    await auth.logout();
    navigate('/login', {
      replace: true,
    });
  };

  const handleProfileOpen = (
    event: MouseEvent<HTMLElement>
  ) => {
    setProfileAnchor(
      event.currentTarget
    );
  };

  return (
    <Box
      sx={{
        minHeight: '100vh',
        backgroundColor:
          'background.default',
      }}
    >
      <LayoutTopBar
        title={pageTitle}
        user={auth.user}
        tenant={auth.tenant}
        profileOpen={
          Boolean(profileAnchor)
        }
        onMenuOpen={() => {
          setMobileOpen(true);
        }}
        onProfileOpen={
          handleProfileOpen
        }
      />

      <LayoutSidebar
        groups={groups}
        pathname={location.pathname}
        tenant={auth.tenant}
        user={auth.user}
        mobileOpen={mobileOpen}
        onMobileClose={() => {
          setMobileOpen(false);
        }}
        onNavigate={
          handleNavigation
        }
        onLogout={() => {
          void handleLogout();
        }}
      />

      <ProfileMenu
        anchor={profileAnchor}
        user={auth.user}
        tenant={auth.tenant}
        onClose={() => {
          setProfileAnchor(null);
        }}
        onDashboard={() => {
          setProfileAnchor(null);
          navigate('/dashboard');
        }}
        onLogout={() => {
          void handleLogout();
        }}
      />

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
