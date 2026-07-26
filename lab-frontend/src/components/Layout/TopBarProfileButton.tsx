import type {
  MouseEvent,
} from 'react';

import {
  Avatar,
  Box,
  Button,
  Typography,
} from '@mui/material';

import KeyboardArrowDownRounded from
  '@mui/icons-material/KeyboardArrowDownRounded';

import type {
  Tenant,
  User,
} from '../../types';

import {
  formatRole,
  getInitials,
} from './layoutUtils';

interface Props {
  user: User | null;
  tenant: Tenant | null;
  open: boolean;
  onOpen: (
    event: MouseEvent<HTMLElement>
  ) => void;
}

const TopBarProfileButton = ({
  user,
  tenant,
  open,
  onOpen,
}: Props) => (
  <Button
    aria-haspopup="menu"
    aria-expanded={
      open ? 'true' : undefined
    }
    onClick={onOpen}
    endIcon={
      <KeyboardArrowDownRounded />
    }
    sx={{
      minWidth: 0,
      minHeight: 48,
      p: 0.55,
      color: 'text.primary',
      border: '1px solid',
      borderColor: 'divider',
      backgroundColor:
        'background.paper',
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
          'linear-gradient(145deg,#2563EB,#0E7490)',
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
        noWrap
        sx={{
          fontSize: '0.72rem',
          fontWeight: 650,
        }}
      >
        {user?.name}
      </Typography>

      <Typography
        noWrap
        sx={{
          color: 'text.secondary',
          fontSize: '0.61rem',
        }}
      >
        {tenant?.name ??
          formatRole(user?.role)}
      </Typography>
    </Box>
  </Button>
);

export default TopBarProfileButton;
