import {
  CheckCircleRounded,
  LockResetRounded,
} from '@mui/icons-material';

import {
  Alert,
  Box,
  Button,
  CircularProgress,
  Container,
  FormControl,
  InputLabel,
  MenuItem,
  Paper,
  Select,
  Stack,
  TextField,
  Typography,
} from '@mui/material';

import {
  useEffect,
  useMemo,
  useState,
} from 'react';

import {
  useNavigate,
  useSearchParams,
} from 'react-router-dom';

import {
  completePortalPasswordReset,
  inspectPortalPasswordReset,
  requestPortalPasswordReset,
} from '../api/portalPublicApi';

import type {
  PortalAccountType,
  PortalPasswordResetInspection,
  PortalPasswordResetResult,
} from '../types/portalAccessAdmin.types';

import {
  apiErrorMessage,
} from '../../test-results/utils/apiErrorMessage';

const readFragmentToken = (): string => {
  if (typeof window === 'undefined') {
    return '';
  }

  return new URLSearchParams(
    window.location.hash.replace(/^#/, '')
  ).get('token')?.trim() ?? '';
};

const PortalPasswordReset = () => {
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const queryType = searchParams.get('type');
  const initialType: PortalAccountType = queryType === 'clinician'
    ? 'clinician'
    : 'patient';
  const initialDomain = searchParams.get('tenant')
    ?.trim()
    .toLowerCase() ?? '';
  const token = useMemo(readFragmentToken, []);

  const [domain, setDomain] = useState(initialDomain);
  const [accountType, setAccountType] =
    useState<PortalAccountType>(initialType);
  const [email, setEmail] = useState('');
  const [inspection, setInspection] =
    useState<PortalPasswordResetInspection | null>(null);
  const [result, setResult] =
    useState<PortalPasswordResetResult | null>(null);
  const [requestMessage, setRequestMessage] =
    useState<string | null>(null);
  const [loading, setLoading] = useState(Boolean(token));
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [password, setPassword] = useState('');
  const [confirmation, setConfirmation] = useState('');

  useEffect(() => {
    if (!token) {
      return;
    }

    let active = true;

    const inspect = async () => {
      if (!initialDomain) {
        setError('This password reset link is incomplete or invalid.');
        setLoading(false);
        return;
      }

      try {
        const next = await inspectPortalPasswordReset(
          initialDomain,
          token
        );

        if (active) {
          setInspection(next);
          setAccountType(next.account_type);
        }
      } catch (requestError: unknown) {
        if (active) {
          setError(apiErrorMessage(
            requestError,
            'This password reset link is invalid or no longer available.'
          ));
        }
      } finally {
        if (active) {
          setLoading(false);
        }
      }
    };

    void inspect();

    return () => {
      active = false;
    };
  }, [initialDomain, token]);

  const requestReset = async () => {
    if (!domain.trim() || !email.trim()) {
      setError('Enter your laboratory domain and portal email address.');
      return;
    }

    setSaving(true);
    setError(null);

    try {
      setRequestMessage(await requestPortalPasswordReset(
        domain.trim().toLowerCase(),
        accountType,
        email.trim().toLowerCase()
      ));
      setEmail('');
    } catch (requestError: unknown) {
      setError(apiErrorMessage(
        requestError,
        'The password reset request could not be accepted.'
      ));
    } finally {
      setSaving(false);
    }
  };

  const completeReset = async () => {
    if (password !== confirmation) {
      setError('Password confirmation does not match.');
      return;
    }

    setSaving(true);
    setError(null);

    try {
      const completed = await completePortalPasswordReset(
        initialDomain,
        token,
        password,
        confirmation
      );
      setResult(completed);
      setInspection(null);

      if (typeof window !== 'undefined') {
        window.history.replaceState(
          null,
          document.title,
          window.location.pathname
        );
      }
    } catch (requestError: unknown) {
      setError(apiErrorMessage(
        requestError,
        'The portal password could not be reset.'
      ));
    } finally {
      setSaving(false);
    }
  };

  const loginPath = accountType === 'clinician'
    ? '/clinician-portal/login'
    : '/patient-portal/login';

  return (
    <Box
      sx={{
        minHeight: '100vh',
        display: 'flex',
        alignItems: 'center',
        py: 5,
        background: (theme) => (
          `linear-gradient(145deg, ${theme.palette.primary.dark}, ${theme.palette.primary.main})`
        ),
      }}
    >
      <Container maxWidth="sm">
        <Paper
          elevation={12}
          sx={{ p: { xs: 3, sm: 5 }, borderRadius: 4 }}
        >
          <Stack spacing={3}>
            <Stack spacing={1} sx={{ alignItems: 'center', textAlign: 'center' }}>
              {result
                ? <CheckCircleRounded color="success" sx={{ fontSize: 54 }} />
                : <LockResetRounded color="primary" sx={{ fontSize: 54 }} />}
              <Typography variant="h4" sx={{ fontWeight: 760 }}>
                {result ? 'Password reset complete' : 'Reset portal password'}
              </Typography>
              <Typography color="text.secondary">
                {token
                  ? 'Establish a new secure password for this portal account.'
                  : 'Request a one-time reset link. The response never reveals whether an account exists.'}
              </Typography>
            </Stack>

            {loading && (
              <Box sx={{ display: 'grid', placeItems: 'center', py: 3 }}>
                <CircularProgress />
              </Box>
            )}

            {error && <Alert severity="error">{error}</Alert>}
            {requestMessage && <Alert severity="success">{requestMessage}</Alert>}

            {!token && !requestMessage && (
              <Stack spacing={2}>
                <TextField
                  required
                  label="Laboratory domain"
                  value={domain}
                  onChange={(event) => setDomain(
                    event.target.value.toLowerCase().replace(/\s+/g, '')
                  )}
                />
                <FormControl fullWidth>
                  <InputLabel id="portal-account-type-label">Portal type</InputLabel>
                  <Select
                    labelId="portal-account-type-label"
                    label="Portal type"
                    value={accountType}
                    onChange={(event) => setAccountType(
                      event.target.value as PortalAccountType
                    )}
                  >
                    <MenuItem value="patient">Patient</MenuItem>
                    <MenuItem value="clinician">Clinician</MenuItem>
                  </Select>
                </FormControl>
                <TextField
                  required
                  type="email"
                  label="Portal email address"
                  value={email}
                  onChange={(event) => setEmail(event.target.value)}
                  autoComplete="email"
                />
                <Button
                  size="large"
                  variant="contained"
                  disabled={saving}
                  onClick={() => void requestReset()}
                >
                  {saving ? 'Requesting…' : 'Send reset link'}
                </Button>
              </Stack>
            )}

            {inspection && !result && (
              <Stack spacing={2}>
                <Alert severity="info">
                  Account: {inspection.email_hint}
                </Alert>
                <TextField
                  required
                  type="password"
                  label="New password"
                  value={password}
                  onChange={(event) => setPassword(event.target.value)}
                  helperText="Use at least 12 characters with upper and lower case letters and a number."
                  autoComplete="new-password"
                />
                <TextField
                  required
                  type="password"
                  label="Confirm password"
                  value={confirmation}
                  onChange={(event) => setConfirmation(event.target.value)}
                  autoComplete="new-password"
                />
                <Button
                  size="large"
                  variant="contained"
                  disabled={
                    saving
                    || password.length < 12
                    || confirmation.length < 12
                  }
                  onClick={() => void completeReset()}
                >
                  {saving ? 'Resetting…' : 'Reset password'}
                </Button>
              </Stack>
            )}

            {(result || requestMessage) && (
              <Button
                size="large"
                variant="contained"
                onClick={() => navigate(loginPath, { replace: true })}
              >
                Return to portal sign in
              </Button>
            )}
          </Stack>
        </Paper>
      </Container>
    </Box>
  );
};

export default PortalPasswordReset;
