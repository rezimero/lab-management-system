import {
  Box,
  Button,
} from '@mui/material';

import LogoutRounded from
  '@mui/icons-material/LogoutRounded';

import type {
  User,
} from '../../types';

interface Props {
  user: User | null;
  onLogout: () => void;
}

const SidebarFooter = ({
  onLogout,
}: Props) => (
  <Box
    sx={{
      p: 2,
      pt: 1.25,
      borderTop:
        '1px solid rgba(255,255,255,.08)',
    }}
  >
    <Button
      fullWidth
      startIcon={<LogoutRounded />}
      onClick={onLogout}
      sx={{
        minHeight: 42,
        justifyContent: 'flex-start',
        color:
          'rgba(255,255,255,.72)',
        fontSize: '0.78rem',
        fontWeight: 560,
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
