import {
  useEffect,
  useMemo,
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
  TenantRecord,
} from './TenantFormDialog';

interface TenantPasswordResetPayload {
  email: string;
  new_password: string;
}

interface TenantPasswordResetDialogProps {
  open: boolean;
  tenant: TenantRecord | null;
  submitting: boolean;
  serverError?: string | null;

  onClose: () => void;

  onSubmit: (
    tenantId: number,
    payload: TenantPasswordResetPayload
  ) => Promise<void>;
}

const validatePassword = (
  password: string
): string | null => {
  if (password.length < 12) {
    return 'Use at least 12 characters.';
  }

  if (
    !/[a-z]/.test(password) ||
    !/[A-Z]/.test(password) ||
    !/[0-9]/.test(password) ||
    !/[^A-Za-z0-9]/.test(password)
  ) {
    return 'Include uppercase, lowercase, number and special character.';
  }

  return null;
};

const TenantPasswordResetDialog = ({
  open,
  tenant,
  submitting,
  serverError,
  onClose,
  onSubmit,
}: TenantPasswordResetDialogProps) => {
  const [
    email,
    setEmail,
  ] = useState('');

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

  const passwordValid =
    useMemo(() => {
      return (
        Boolean(password) &&
        validatePassword(password) ===
          null &&
        password === confirmation
      );
    }, [confirmation, password]);

  useEffect(() => {
    if (!open) {
      return;
    }

    setEmail('');
    setPassword('');
    setConfirmation('');
    setShowPassword(false);
    setValidationError(null);
  }, [open, tenant]);

  const handleSubmit =
    async (): Promise<void> => {
      if (!tenant) {
        return;
      }

      if (
        !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(
          email.trim()
        )
      ) {
        setValidationError(
          'Enter the email address of the tenant administrator.'
        );

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

      await onSubmit(tenant.id, {
        email: email
          .trim()
          .toLowerCase(),
        new_password: password,
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

          <Box>
            <Typography
              sx={{
                fontSize: '0.98rem',
                fontWeight: 710,
              }}
            >
              Reset administrator password
            </Typography>

            <Typography
              sx={{
                mt: 0.25,
                color: 'text.secondary',
                fontSize: '0.68rem',
              }}
            >
              {tenant?.name ??
                'Tenant laboratory'}
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
          This is a privileged security action.
          Confirm the administrator’s identity
          before communicating the temporary
          password.
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
            type="email"
            label="Administrator email"
            value={email}
            onChange={(event) => {
              setEmail(
                event.target.value
              );
            }}
          />

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
          disabled={
            submitting ||
            !passwordValid
          }
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

export default TenantPasswordResetDialog;