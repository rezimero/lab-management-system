import type {
  MouseEvent,
} from 'react';

import {
  AppBar,
  Box,
  Chip,
  IconButton,
  Toolbar,
  Typography,
  alpha,
  useTheme,
} from '@mui/material';

import MenuRounded from
  '@mui/icons-material/MenuRounded';

import ShieldRounded from
  '@mui/icons-material/ShieldRounded';

import type {
  Tenant,
  User,
} from '../../types';

import {
  drawerWidth,
  topBarHeight,
} from './layoutConstants';

import TopBarProfileButton from
  './TopBarProfileButton';

interface Props {
  title: string;
  user: User | null;
  tenant: Tenant | null;
  profileOpen: boolean;
  onMenuOpen: () => void;
  onProfileOpen: (
    event: MouseEvent<HTMLElement>
  ) => void;
}

const LayoutTopBar = ({
  title,
  user,
  tenant,
  profileOpen,
  onMenuOpen,
  onProfileOpen,
}: Props) => {
  const theme = useTheme();

  return (
    <AppBar
      position="fixed"
      color="transparent"
      sx={{
        width: {
          xs: '100%',
          md:
            `calc(100% - ${drawerWidth}px)`,
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
          alpha('#FFFFFF', 0.88),
        backdropFilter:
          'blur(18px) saturate(150%)',
        boxShadow:
          '0 1px 2px rgba(11,18,32,.025)',
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
          onClick={onMenuOpen}
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
            Rezhel workspace
          </Typography>

          <Typography
            component="h1"
            noWrap
            sx={{
              mt: 0.2,
              color: 'text.primary',
              fontSize: {
                xs: '0.98rem',
                sm: '1.08rem',
              },
              fontWeight: 710,
            }}
          >
            {title}
          </Typography>
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
            mr: 1,
            color: 'success.dark',
            backgroundColor:
              alpha('#079455', 0.075),
          }}
        />

        <TopBarProfileButton
          user={user}
          tenant={tenant}
          open={profileOpen}
          onOpen={onProfileOpen}
        />
      </Toolbar>
    </AppBar>
  );
};

export default LayoutTopBar;
