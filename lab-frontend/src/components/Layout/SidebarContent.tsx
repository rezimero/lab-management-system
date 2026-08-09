import {
  Box,
  Typography,
} from '@mui/material';

import DomainRounded from
  '@mui/icons-material/DomainRounded';

import type {
  Tenant,
  User,
} from '../../types';

import LabBrand from
  '../brand/LabBrand';

import type {
  NavigationGroup,
} from './navigationConfig';

import SidebarFooter from
  './SidebarFooter';

import SidebarNavigation from
  './SidebarNavigation';

interface Props {
  groups: NavigationGroup[];
  pathname: string;
  tenant: Tenant | null;
  user: User | null;
  onNavigate: (path: string) => void;
  onLogout: () => void;
}

const SidebarContent = ({
  groups,
  pathname,
  tenant,
  user,
  onNavigate,
  onLogout,
}: Props) => (
  <Box
    sx={{
      height: '100%',
      display: 'flex',
      flexDirection: 'column',
      overflow: 'hidden',
      color: '#FFFFFF',
      background:
        'linear-gradient(180deg,#07111F 0%,#091727 52%,#08131F 100%)',
    }}
  >
    <Box
      sx={{
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
        mx: 2,
        mb: 2.5,
        p: 1.75,
        display: 'flex',
        alignItems: 'center',
        gap: 1.25,
        borderRadius: 3.25,
        border:
          '1px solid rgba(255,255,255,.10)',
        background:
          'rgba(255,255,255,.055)',
      }}
    >
      <Box
        sx={{
          width: 38,
          height: 38,
          display: 'grid',
          placeItems: 'center',
          borderRadius: 2.4,
          color: '#A5F3FC',
          backgroundColor:
            'rgba(8,145,178,.16)',
        }}
      >
        <DomainRounded
          sx={{ fontSize: 20 }}
        />
      </Box>

      <Box
        sx={{
          minWidth: 0,
          flex: 1,
        }}
      >
        <Typography
          noWrap
          sx={{
            color: '#FFFFFF',
            fontSize: '0.82rem',
            fontWeight: 680,
          }}
        >
          {tenant?.name ??
            'Rezhel Platform'}
        </Typography>

        <Typography
          noWrap
          sx={{
            mt: 0.25,
            color:
              'rgba(255,255,255,.62)',
            fontSize: '0.7rem',
          }}
        >
          {tenant?.domain
            ? `${tenant.domain} workspace`
            : 'Platform workspace'}
        </Typography>
      </Box>
    </Box>

    <SidebarNavigation
      groups={groups}
      pathname={pathname}
      onNavigate={onNavigate}
    />

    <SidebarFooter
      user={user}
      onLogout={onLogout}
    />
  </Box>
);

export default SidebarContent;
