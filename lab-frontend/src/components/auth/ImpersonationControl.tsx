import EngineeringRounded from '@mui/icons-material/EngineeringRounded';
import KeyboardReturnRounded from '@mui/icons-material/KeyboardReturnRounded';
import ShieldRounded from '@mui/icons-material/ShieldRounded';

import {
  Box,
  Button,
  Chip,
  CircularProgress,
  Paper,
  Typography,
} from '@mui/material';

import {
  alpha,
} from '@mui/material/styles';

import {
  useState,
} from 'react';

import toast from 'react-hot-toast';

import {
  useNavigate,
} from 'react-router-dom';

import {
  useAuth,
} from '../../contexts/AuthContext';

const ImpersonationControl = () => {
  const navigate = useNavigate();

  const {
    tenant,
    impersonator,
    isImpersonating,
    stopImpersonation,
  } = useAuth();

  const [
    stopping,
    setStopping,
  ] = useState(false);

  if (!isImpersonating) {
    return null;
  }

  const handleReturnToPlatform =
    async (): Promise<void> => {
      setStopping(true);

      try {
        await stopImpersonation();

        toast.success(
          'Returned to Rezhel platform control.'
        );

        navigate('/landlord', {
          replace: true,
        });
      } catch (error) {
        const message =
          error instanceof Error
            ? error.message
            : 'The platform session could not be restored.';

        toast.error(message);
      } finally {
        setStopping(false);
      }
    };

  return (
    <Paper
      role="status"
      aria-live="polite"
      sx={{
        position: 'fixed',
        right: {
          xs: 12,
          sm: 20,
        },
        bottom: {
          xs: 12,
          sm: 20,
        },
        zIndex: 1500,
        width: {
          xs: 'calc(100% - 24px)',
          sm: 410,
        },
        overflow: 'hidden',
        borderRadius: 4,
        border:
          '1px solid rgba(103, 232, 249, 0.18)',
        color: '#FFFFFF',
        background:
          'linear-gradient(145deg, rgba(7,17,31,0.97), rgba(9,39,55,0.97))',
        boxShadow:
          '0 24px 70px rgba(7,17,31,0.32)',
        backdropFilter: 'blur(18px)',
      }}
    >
      <Box
        sx={{
          display: 'flex',
          alignItems: 'flex-start',
          gap: 1.5,
          p: 2,
        }}
      >
        <Box
          sx={{
            width: 43,
            height: 43,
            flexShrink: 0,
            display: 'grid',
            placeItems: 'center',
            borderRadius: 2.75,
            color: '#A5F3FC',
            backgroundColor:
              'rgba(8,145,178,0.15)',
            border:
              '1px solid rgba(103,232,249,0.1)',
          }}
        >
          <EngineeringRounded
            sx={{
              fontSize: 22,
            }}
          />
        </Box>

        <Box
          sx={{
            minWidth: 0,
            flex: 1,
          }}
        >
          <Box
            sx={{
              display: 'flex',
              alignItems: 'center',
              flexWrap: 'wrap',
              gap: 0.75,
            }}
          >
            <Typography
              sx={{
                fontSize: '0.82rem',
                fontWeight: 700,
              }}
            >
              Tenant maintenance access
            </Typography>

            <Chip
              icon={
                <ShieldRounded
                  sx={{
                    fontSize:
                      '14px !important',
                  }}
                />
              }
              label="Superadmin"
              size="small"
              sx={{
                height: 24,
                color: '#A5F3FC',
                backgroundColor:
                  'rgba(8,145,178,0.13)',

                '& .MuiChip-icon': {
                  color: '#67E8F9',
                },
              }}
            />
          </Box>

          <Typography
            sx={{
              mt: 0.6,
              overflow: 'hidden',
              color: '#FFFFFF',
              fontSize: '0.76rem',
              fontWeight: 640,
              textOverflow: 'ellipsis',
              whiteSpace: 'nowrap',
            }}
          >
            {tenant?.name ??
              'Tenant workspace'}
          </Typography>

          <Typography
            sx={{
              mt: 0.35,
              color:
                alpha('#FFFFFF', 0.48),
              fontSize: '0.65rem',
              lineHeight: 1.5,
            }}
          >
            Acting through a tenant administrator
            account on behalf of{' '}
            {impersonator?.name ??
              'the Rezhel superadmin'}.
          </Typography>
        </Box>
      </Box>

      <Box
        sx={{
          display: 'flex',
          justifyContent: 'flex-end',
          px: 2,
          py: 1.35,
          borderTop:
            '1px solid rgba(255,255,255,0.07)',
          backgroundColor:
            'rgba(255,255,255,0.025)',
        }}
      >
        <Button
          variant="contained"
          size="small"
          disabled={stopping}
          startIcon={
            stopping ? (
              <CircularProgress
                size={15}
                thickness={4}
                sx={{
                  color: 'inherit',
                }}
              />
            ) : (
              <KeyboardReturnRounded />
            )
          }
          onClick={() => {
            void handleReturnToPlatform();
          }}
          sx={{
            color: '#07111F',
            background:
              'linear-gradient(135deg, #A5F3FC, #67E8F9)',

            '&:hover': {
              background:
                'linear-gradient(135deg, #CFFAFE, #A5F3FC)',
            },
          }}
        >
          {stopping
            ? 'Restoring platform'
            : 'Return to platform'}
        </Button>
      </Box>
    </Paper>
  );
};

export default ImpersonationControl;