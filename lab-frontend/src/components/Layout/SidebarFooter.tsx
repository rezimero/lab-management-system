import {
  Avatar,
  Box,
  Button,
  Typography,
} from '@mui/material';

import LogoutRounded from
  '@mui/icons-material/LogoutRounded';

import type {
  User,
} from '../../types';

import {
  formatRole,
  getInitials,
} from './layoutUtils';

interface Props {
  user: User | null;
  onLogout: () => void;
}

const SidebarFooter = ({
  user,
  onLogout,
}: Props) => (
  <Box
    sx={{
      p: 2,
      pt: 1.5,
      borderTop:
        '1px solid rgba(255,255,255,.06)',
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
          'rgba(255,255,255,.035)',
      }}
    >
      <Avatar
        sx={{
          width: 40,
          height: 40,
          color: '#FFFFFF',
          fontSize: '0.76rem',
          fontWeight: 720,
          background:
            'linear-gradient(145deg,#2563EB,#0E7490)',
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
          noWrap
          sx={{
            color: '#FFFFFF',
            fontSize: '0.75rem',
            fontWeight: 640,
          }}
        >
          {user?.name ?? 'Rezhel User'}
        </Typography>

        <Typography
          noWrap
          sx={{
            mt: 0.1,
            color:
              'rgba(255,255,255,.38)',
            fontSize: '0.64rem',
          }}
        >
          {formatRole(user?.role)}
        </Typography>
      </Box>
    </Box>

    <Button
      fullWidth
      startIcon={<LogoutRounded />}
      onClick={onLogout}
      sx={{
        mt: 1,
        minHeight: 40,
        justifyContent: 'flex-start',
        color:
          'rgba(255,255,255,.48)',
        fontSize: '0.73rem',
        '&:hover': {
          color: '#FDA29B',
          backgroundColor:
            'rgba(217,45,32,.09)',
        },
      }}
    >
      Sign out securely
    </Button>
  </Box>
);

export default SidebarFooter;
