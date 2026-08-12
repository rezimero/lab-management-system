import {
  FormEvent,
  useEffect,
  useMemo,
  useState,
} from 'react';

import ArrowForwardRounded from
  '@mui/icons-material/ArrowForwardRounded';
import DomainRounded from
  '@mui/icons-material/DomainRounded';
import EmailRounded from
  '@mui/icons-material/EmailRounded';
import LockRounded from
  '@mui/icons-material/LockRounded';
import ScienceRounded from
  '@mui/icons-material/ScienceRounded';
import ShieldRounded from
  '@mui/icons-material/ShieldRounded';
import VisibilityOffRounded from
  '@mui/icons-material/VisibilityOffRounded';
import VisibilityRounded from
  '@mui/icons-material/VisibilityRounded';

import {
  Alert,
  Box,
  Button,
  Card,
  CardContent,
  CircularProgress,
  Container,
  IconButton,
  InputAdornment,
  TextField,
  Typography,
} from '@mui/material';

import {
  alpha,
} from '@mui/material/styles';

import {
  Link,
  useNavigate,
  useSearchParams,
} from 'react-router-dom';

import {
  fetchPortalTenantContext,
} from '../../portal-access/api/portalPublicApi';

import {
  usePatientPortalAuth,
} from '../context/PatientPortalAuthContext';

import {
  patientPortalErrorMessage,
} from '../utils/patientPortalDisplay';

interface LoginFormState {
  domain: string;
  email: string;
  password: string;
}

const initialForm: LoginFormState = {
  domain: '',
  email: '',
  password: '',
};

const PatientPortalLogin = () => {
  const auth = usePatientPortalAuth();
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();

  const initialDomain = useMemo(
    () => (
      searchParams.get('domain')
        ?.trim()
        .toLowerCase()
        .replace(/\s+/g, '')
      ?? ''
    ),
    [searchParams]
  );

  const [
    form,
    setForm,
  ] = useState<LoginFormState>(() => ({
    ...initialForm,
    domain: initialDomain,
  }));

  const [
    showPassword,
    setShowPassword,
  ] = useState(false);

  const [
    submitting,
    setSubmitting,
  ] = useState(false);

  const [
    error,
    setError,
  ] = useState<string | null>(null);

  const [
    tenantName,
    setTenantName,
  ] = useState<string | null>(null);

  const normalizedDomain = useMemo(
    () => form.domain
      .trim()
      .toLowerCase(),
    [form.domain]
  );

  useEffect(() => {
    if (normalizedDomain.length < 2) {
      setTenantName(null);
      return;
    }

    let active = true;

    const timer = window.setTimeout(
      () => {
        void fetchPortalTenantContext(
          normalizedDomain
        )
          .then((tenant) => {
            if (active) {
              setTenantName(tenant.name);
            }
          })
          .catch(() => {
            if (active) {
              setTenantName(null);
            }
          });
      },
      350
    );

    return () => {
      active = false;
      window.clearTimeout(timer);
    };
  }, [normalizedDomain]);

  const portalTitle = tenantName
    ? `${tenantName} Patient Portal`
    : 'Patient Portal';

  const updateField = (
    field: keyof LoginFormState,
    value: string
  ): void => {
    setForm((current) => ({
      ...current,
      [field]: value,
    }));

    if (error) {
      setError(null);
    }
  };

  const handleSubmit = async (
    event: FormEvent<HTMLFormElement>
  ): Promise<void> => {
    event.preventDefault();
    setError(null);

    const domain =
      form.domain.trim().toLowerCase();

    const email =
      form.email.trim().toLowerCase();

    if (
      !domain
      || !email
      || !form.password
    ) {
      setError(
        'Enter your laboratory domain, email address and password.'
      );
      return;
    }

    setSubmitting(true);

    try {
      await auth.login(
        domain,
        email,
        form.password
      );

      navigate(
        '/patient-portal',
        { replace: true }
      );
    } catch (caughtError) {
      setError(
        patientPortalErrorMessage(
          caughtError,
          'We could not sign you in. Please verify your details.'
        )
      );
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <Box
      sx={{
        minHeight: '100vh',
        display: 'grid',
        placeItems: 'center',
        py: 5,
        background:
          'radial-gradient(circle at top left, rgba(36,107,253,0.16), transparent 34%), linear-gradient(160deg, #F8FBFF 0%, #F5F7FB 56%, #EEF3FA 100%)',
      }}
    >
      <Container maxWidth="sm">
        <Box
          sx={{
            display: 'flex',
            justifyContent: 'center',
            mb: 3,
          }}
        >
          <Box
            sx={{
              display: 'inline-flex',
              alignItems: 'center',
              gap: 1.2,
            }}
          >
            <Box
              sx={{
                width: 44,
                height: 44,
                display: 'grid',
                placeItems: 'center',
                borderRadius: '14px',
                color: 'common.white',
                background:
                  'linear-gradient(135deg, #246BFD 0%, #1747A6 100%)',
                boxShadow:
                  '0 10px 28px rgba(36,107,253,0.25)',
              }}
            >
              <ScienceRounded />
            </Box>

            <Box>
              <Typography
                sx={{
                  fontWeight: 780,
                  lineHeight: 1.1,
                }}
              >
                {portalTitle}
              </Typography>

              <Typography
                sx={{
                  mt: 0.25,
                  color: 'text.secondary',
                  fontSize: '0.72rem',
                }}
              >
                Secure laboratory access
              </Typography>
            </Box>
          </Box>
        </Box>

        <Card
          sx={{
            overflow: 'hidden',
            borderRadius: '24px',
            boxShadow:
              '0 24px 70px rgba(15, 23, 42, 0.10)',
          }}
        >
          <CardContent
            sx={{
              p: {
                xs: 3,
                sm: 4.5,
              },
              '&:last-child': {
                pb: {
                  xs: 3,
                  sm: 4.5,
                },
              },
            }}
          >
            <Typography
              variant="h4"
              component="h1"
              sx={{
                fontWeight: 780,
                letterSpacing: '-0.025em',
              }}
            >
              Welcome back.
            </Typography>

            <Typography
              sx={{
                mt: 1,
                mb: 3.25,
                color: 'text.secondary',
                lineHeight: 1.65,
              }}
            >
              Sign in with the patient portal access
              issued by {tenantName ?? 'your laboratory'}.
            </Typography>

            {error && (
              <Alert
                severity="error"
                sx={{ mb: 2.5 }}
              >
                {error}
              </Alert>
            )}

            <Box
              component="form"
              noValidate
              onSubmit={handleSubmit}
              sx={{
                display: 'grid',
                gap: 2.15,
              }}
            >
              <TextField
                fullWidth
                required
                autoFocus
                label="Laboratory domain"
                placeholder="e.g. development"
                value={form.domain}
                onChange={(event) => {
                  updateField(
                    'domain',
                    event.target.value
                      .toLowerCase()
                      .replace(/\s+/g, '')
                  );
                }}
                autoComplete="organization"
                slotProps={{
                  input: {
                    startAdornment: (
                      <InputAdornment position="start">
                        <DomainRounded
                          sx={{
                            color: 'text.disabled',
                            fontSize: 20,
                          }}
                        />
                      </InputAdornment>
                    ),
                  },
                }}
              />

              <TextField
                fullWidth
                required
                label="Email address"
                type="email"
                value={form.email}
                onChange={(event) => {
                  updateField(
                    'email',
                    event.target.value
                  );
                }}
                autoComplete="email"
                slotProps={{
                  input: {
                    startAdornment: (
                      <InputAdornment position="start">
                        <EmailRounded
                          sx={{
                            color: 'text.disabled',
                            fontSize: 20,
                          }}
                        />
                      </InputAdornment>
                    ),
                  },
                }}
              />

              <TextField
                fullWidth
                required
                label="Password"
                type={
                  showPassword
                    ? 'text'
                    : 'password'
                }
                value={form.password}
                onChange={(event) => {
                  updateField(
                    'password',
                    event.target.value
                  );
                }}
                autoComplete="current-password"
                slotProps={{
                  input: {
                    startAdornment: (
                      <InputAdornment position="start">
                        <LockRounded
                          sx={{
                            color: 'text.disabled',
                            fontSize: 20,
                          }}
                        />
                      </InputAdornment>
                    ),
                    endAdornment: (
                      <InputAdornment position="end">
                        <IconButton
                          edge="end"
                          size="small"
                          type="button"
                          aria-label={
                            showPassword
                              ? 'Hide password'
                              : 'Show password'
                          }
                          onClick={() => {
                            setShowPassword(
                              (current) => !current
                            );
                          }}
                        >
                          {showPassword ? (
                            <VisibilityOffRounded
                              sx={{ fontSize: 20 }}
                            />
                          ) : (
                            <VisibilityRounded
                              sx={{ fontSize: 20 }}
                            />
                          )}
                        </IconButton>
                      </InputAdornment>
                    ),
                  },
                }}
              />

              <Button
                fullWidth
                type="submit"
                size="large"
                variant="contained"
                disabled={
                  submitting || auth.isLoading
                }
                endIcon={
                  submitting
                    ? undefined
                    : <ArrowForwardRounded />
                }
                sx={{
                  mt: 0.75,
                  minHeight: 52,
                  background:
                    'linear-gradient(135deg, #246BFD 0%, #1D4ED8 100%)',
                }}
              >
                {submitting ? (
                  <Box
                    sx={{
                      display: 'inline-flex',
                      alignItems: 'center',
                      gap: 1.1,
                    }}
                  >
                    <CircularProgress
                      size={18}
                      thickness={4}
                      sx={{ color: 'inherit' }}
                    />
                    Verifying access
                  </Box>
                ) : (
                  'Open patient portal'
                )}
              </Button>
            </Box>

            <Box
              sx={{
                display: 'flex',
                alignItems: 'flex-start',
                gap: 1.1,
                mt: 2.5,
                p: 1.5,
                borderRadius: '12px',
                color: 'text.secondary',
                backgroundColor:
                  alpha('#079455', 0.055),
              }}
            >
              <ShieldRounded
                sx={{
                  mt: 0.1,
                  flexShrink: 0,
                  color: 'success.main',
                  fontSize: 18,
                }}
              />

              <Typography
                sx={{
                  fontSize: '0.74rem',
                  lineHeight: 1.6,
                }}
              >
                Your portal session is isolated from
                laboratory staff access and scoped to
                your own records.
              </Typography>
            </Box>

            <Typography
              sx={{
                mt: 3,
                pt: 2.5,
                borderTop: '1px solid',
                borderColor: 'divider',
                color: 'text.secondary',
                textAlign: 'center',
                fontSize: '0.78rem',
              }}
            >
              Laboratory staff?{' '}
              <Typography
                component={Link}
                to="/login"
                sx={{
                  color: 'primary.main',
                  fontSize: 'inherit',
                  fontWeight: 680,
                  textDecoration: 'none',
                  '&:hover': {
                    textDecoration: 'underline',
                  },
                }}
              >
                Staff sign in
              </Typography>
            </Typography>
          </CardContent>
        </Card>
      </Container>
    </Box>
  );
};

export default PatientPortalLogin;
