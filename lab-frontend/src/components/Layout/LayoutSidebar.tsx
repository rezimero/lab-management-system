import {
  Drawer,
} from '@mui/material';

import type {
  Tenant,
  User,
} from '../../types';

import {
  drawerWidth,
} from './layoutConstants';

import type {
  NavigationGroup,
} from './navigationConfig';

import SidebarContent from
  './SidebarContent';

interface Props {
  groups: NavigationGroup[];
  pathname: string;
  tenant: Tenant | null;
  user: User | null;
  mobileOpen: boolean;
  onMobileClose: () => void;
  onNavigate: (path: string) => void;
  onLogout: () => void;
}

const LayoutSidebar = ({
  groups,
  pathname,
  tenant,
  user,
  mobileOpen,
  onMobileClose,
  onNavigate,
  onLogout,
}: Props) => {
  const content = (
    <SidebarContent
      groups={groups}
      pathname={pathname}
      tenant={tenant}
      user={user}
      onNavigate={onNavigate}
      onLogout={onLogout}
    />
  );

  return (
    <>
      <Drawer
        variant="temporary"
        open={mobileOpen}
        onClose={onMobileClose}
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
        {content}
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
              '12px 0 42px rgba(7,17,31,.08)',
          },
        }}
      >
        {content}
      </Drawer>
    </>
  );
};

export default LayoutSidebar;
