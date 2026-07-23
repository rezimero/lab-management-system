import {
  useEffect,
  useState,
} from 'react';

import LockResetRounded from '@mui/icons-material/LockResetRounded';
import VisibilityOffRounded from '@mui/icons-material/VisibilityOffRounded';
import VisibilityRounded from '@mui/icons-material/VisibilityRounded';

import {
  Alert,
  Box,
  Button,
  CircularProgress,
  Dialog,
  DialogActions,
  DialogContent,
  DialogTitle,
  IconButton,
  InputAdornment,
  TextField,
  Typography,
} from '@mui/material';

import {
  PlatformUserRecord,
} from './PlatformUserDialog';

export interface PlatformPasswordPayload {
  tenant_id: number;
  password: string;
  password_confirmation: string;
}

interface PlatformUserPasswordDialogProps {
  open: boolean;
  user: PlatformUserRecord | null;
  submitting: boolean;
  serverError?: string | null;

  onClose: () => void;

  onSubmit: (
    user: PlatformUserRecord,
    payload: PlatformPasswordPayload
  ) => Promise<void>;
}

const validatePassword = (
  password: string
): string | null => {
  if (password.length < 12) {
    return 'Use at least 12 characters.';
  }

  if (!/[a-z]/.test(password)) {
    return 'Include at least one lowercase letter.';
  }

  if (!/[A-Z]/.test(password)) {
    return 'Include at least one uppercase letter.';
  }

  if (!/[0-9]/.test(password)) {
    return 'Include at least one number.';
  }

  if (!/[^A-Za-z0-9]/.test(password)) {
    return 'Include at least one special character.';
  }

  return null;
};

const PlatformUserPasswordDialog = ({
  open,
  user,
  submitting,
  serverError,
  onClose,
  onSubmit,
}: PlatformUserPasswordDialogProps) => {
  const [
    password,
    setPassword,
  ] = useState('');

  const [
    confirmation,
    setConfirmation,
  ] = useState('');

  const [
    showPassword,
    setShowPassword,
  ] = useState(false);

  const [
    validationError,
    setValidationError,
  ] = useState<string | null>(null);

  useEffect(() => {
    if (!open) {
      return;
    }

    setPassword('');
    setConfirmation('');
    setShowPassword(false);
    setValidationError(null);
  }, [open, user]);

  const handleSubmit =
    async (): Promise<void> => {
      if (!user) {
        return;
      }

      const passwordError =
        validatePassword(password);

      if (passwordError) {
        setValidationError(
          passwordError
        );

        return;
      }

      if (password !== confirmation) {
        setValidationError(
          'The passwords do not match.'
        );

        return;
      }

      setValidationError(null);

      await onSubmit(user, {
        tenant_id: user.tenant_id,
        password,
        password_confirmation:
          confirmation,
      });
    };

  return (
    <Dialog
      open={open}
      onClose={
        submitting
          ? undefined
          : onClose
      }
      maxWidth="sm"
      fullWidth
      slotProps={{
        paper: {
          sx: {
            borderRadius: 4,
          },
        },
      }}
    >
      <DialogTitle>
        <Box
          sx={{
            display: 'flex',
            alignItems: 'center',
            gap: 1.25,
          }}
        >
          <Box
            sx={{
              width: 44,
              height: 44,
              flexShrink: 0,
              display: 'grid',
              placeItems: 'center',
              borderRadius: 2.75,
              color: '#B54708',
              backgroundColor:
                'rgba(220,104,3,0.08)',
            }}
          >
            <LockResetRounded />
          </Box>

          <Box sx={{ minWidth: 0 }}>
            <Typography
              sx={{
                fontSize: '0.98rem',
                fontWeight: 710,
              }}
            >
              Reset user password
            </Typography>

            <Typography
              sx={{
                mt: 0.25,
                overflow: 'hidden',
                color: 'text.secondary',
                fontSize: '0.67rem',
                textOverflow: 'ellipsis',
                whiteSpace: 'nowrap',
              }}
            >
              {user?.name} ·{' '}
              {user?.tenant_name}
            </Typography>
          </Box>
        </Box>
      </DialogTitle>

      <DialogContent>
        {(validationError ||
          serverError) && (
          <Alert
            severity="error"
            sx={{
              mb: 2,
            }}
          >
            {validationError ??
              serverError}
          </Alert>
        )}

        <Alert
          severity="warning"
          sx={{
            mb: 2.5,
          }}
        >
          Existing user sessions will be revoked
          immediately after this password reset.
        </Alert>

        <Box
          sx={{
            display: 'grid',
            gap: 2,
          }}
        >
          <TextField
            fullWidth
            required
            label="New temporary password"
            type={
              showPassword
                ? 'text'
                : 'password'
            }
            value={password}
            helperText="Minimum 12 characters with uppercase, lowercase, number and symbol."
            onChange={(event) => {
              setPassword(
                event.target.value
              );
            }}
            slotProps={{
              input: {
                endAdornment: (
                  <InputAdornment position="end">
                    <IconButton
                      edge="end"
                      aria-label={
                        showPassword
                          ? 'Hide password'
                          : 'Show password'
                      }
                      onClick={() => {
                        setShowPassword(
                          (current) =>
                            !current
                        );
                      }}
                    >
                      {showPassword ? (
                        <VisibilityOffRounded />
                      ) : (
                        <VisibilityRounded />
                      )}
                    </IconButton>
                  </InputAdornment>
                ),
              },
            }}
          />

          <TextField
            fullWidth
            required
            label="Confirm temporary password"
            type={
              showPassword
                ? 'text'
                : 'password'
            }
            value={confirmation}
            onChange={(event) => {
              setConfirmation(
                event.target.value
              );
            }}
          />
        </Box>
      </DialogContent>

      <DialogActions
        sx={{
          px: 3,
          pb: 2.5,
        }}
      >
        <Button
          variant="outlined"
          disabled={submitting}
          onClick={onClose}
        >
          Cancel
        </Button>

        <Button
          variant="contained"
          color="warning"
          disabled={submitting}
          startIcon={
            submitting ? (
              <CircularProgress
                size={16}
                color="inherit"
              />
            ) : (
              <LockResetRounded />
            )
          }
          onClick={() => {
            void handleSubmit();
          }}
        >
          {submitting
            ? 'Resetting password'
            : 'Reset password'}
        </Button>
      </DialogActions>
    </Dialog>
  );
};

export default PlatformUserPasswordDialog;