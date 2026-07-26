import {
  Box,
  Chip,
  Divider,
  Menu,
  MenuItem,
  Typography,
} from '@mui/material';

import AccountCircleRounded from
  '@mui/icons-material/AccountCircleRounded';

import LogoutRounded from
  '@mui/icons-material/LogoutRounded';

import type {
  Tenant,
  User,
} from '../../types';

import {
  formatRole,
} from './layoutUtils';

interface Props {
  anchor: HTMLElement | null;
  user: User | null;
  tenant: Tenant | null;
  onClose: () => void;
  onDashboard: () => void;
  onLogout: () => void;
}

const ProfileMenu = ({
  anchor,
  user,
  tenant,
  onClose,
  onDashboard,
  onLogout,
}: Props) => (
  <Menu
    anchorEl={anchor}
    open={Boolean(anchor)}
    onClose={onClose}
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
          'linear-gradient(145deg,rgba(36,107,253,.06),rgba(8,145,178,.025))',
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
        noWrap
        sx={{
          mt: 0.35,
          color: 'text.secondary',
          fontSize: '0.7rem',
        }}
      >
        {user?.email}
      </Typography>

      <Typography
        noWrap
        sx={{
          mt: 0.35,
          color: 'text.secondary',
          fontSize: '0.7rem',
        }}
      >
        {tenant?.name ??
          'Rezhel Platform'}
      </Typography>

      <Chip
        label={formatRole(user?.role)}
        size="small"
        sx={{
          mt: 1.25,
          color: 'primary.dark',
          backgroundColor:
            'rgba(36,107,253,.075)',
        }}
      />
    </Box>

    <Divider />

    <MenuItem onClick={onDashboard}>
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
      onClick={onLogout}
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
);

export default ProfileMenu;
