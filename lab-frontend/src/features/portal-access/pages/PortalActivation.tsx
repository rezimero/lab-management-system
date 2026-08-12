import {
  CheckCircleRounded,
  LockRounded,
} from '@mui/icons-material';

import {
  Alert,
  Box,
  Button,
  CircularProgress,
  Container,
  Paper,
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
  activatePortalInvitation,
  inspectPortalInvitation,
} from '../api/portalPublicApi';

import type {
  PortalActivationResult,
  PortalInvitationInspection,
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

const PortalActivation = () => {
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const domain = searchParams.get('tenant')
    ?.trim()
    .toLowerCase() ?? '';
  const token = useMemo(readFragmentToken, []);

  const [inspection, setInspection] =
    useState<PortalInvitationInspection | null>(null);
  const [result, setResult] =
    useState<PortalActivationResult | null>(null);
  const [loading, setLoading] =
    useState(true);
  const [saving, setSaving] =
    useState(false);
  const [error, setError] =
    useState<string | null>(null);
  const [password, setPassword] =
    useState('');
  const [confirmation, setConfirmation] =
    useState('');

  useEffect(() => {
    let active = true;

    const inspect = async () => {
      if (!domain || !token) {
        setError(
          'This activation link is incomplete or invalid.'
        );
        setLoading(false);
        return;
      }

      try {
        const next = await inspectPortalInvitation(
          domain,
          token
        );

        if (active) {
          setInspection(next);
        }
      } catch (requestError: unknown) {
        if (active) {
          setError(apiErrorMessage(
            requestError,
            'This activation link is invalid or no longer available.'
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
  }, [domain, token]);

  const activate = async () => {
    if (password !== confirmation) {
      setError('Password confirmation does not match.');
      return;
    }

    setSaving(true);
    setError(null);

    try {
      const activated = await activatePortalInvitation(
        domain,
        token,
        password,
        confirmation
      );
      setResult(activated);
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
        'Portal activation failed.'
      ));
    } finally {
      setSaving(false);
    }
  };

  const loginPath = result?.account_type === 'clinician'
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
            <Stack
              spacing={1}
              sx={{
                alignItems: 'center',
                textAlign: 'center',
              }}
            >
              {result
                ? <CheckCircleRounded color="success" sx={{ fontSize: 54 }} />
                : <LockRounded color="primary" sx={{ fontSize: 54 }} />}

              <Typography variant="h4" sx={{ fontWeight: 760 }}>
                {result
                  ? 'Portal account activated'
                  : 'Activate portal access'}
              </Typography>

              <Typography color="text.secondary">
                {result
                  ? 'Your password has been established. You can now sign in securely.'
                  : inspection
                    ? `${inspection.display_name}, establish your portal password.`
                    : 'Validating your one-time invitation…'}
              </Typography>
            </Stack>

            {loading && (
              <Box sx={{ display: 'flex', justifyContent: 'center', py: 3 }}>
                <CircularProgress />
              </Box>
            )}

            {error && (
              <Alert severity="error">{error}</Alert>
            )}

            {inspection && !result && (
              <Stack spacing={2}>
                <Alert severity="info">
                  Account: {inspection.email}
                </Alert>

                <TextField
                  required
                  type="password"
                  label="New password"
                  value={password}
                  onChange={(event) => {
                    setPassword(event.target.value);
                  }}
                  helperText="Use at least 12 characters with upper and lower case letters and a number."
                  autoComplete="new-password"
                />

                <TextField
                  required
                  type="password"
                  label="Confirm password"
                  value={confirmation}
                  onChange={(event) => {
                    setConfirmation(event.target.value);
                  }}
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
                  onClick={() => {
                    void activate();
                  }}
                >
                  {saving ? 'Activating…' : 'Activate account'}
                </Button>
              </Stack>
            )}

            {result && (
              <Button
                size="large"
                variant="contained"
                onClick={() => navigate(
                  `${loginPath}?domain=${encodeURIComponent(domain)}`
                )}
              >
                Continue to sign in
              </Button>
            )}
          </Stack>
        </Paper>
      </Container>
    </Box>
  );
};

export default PortalActivation;
