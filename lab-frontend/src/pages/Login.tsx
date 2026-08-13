import {
  FormEvent,
  useEffect,
  useMemo,
  useState,
} from 'react';

import AdminPanelSettingsRounded from '@mui/icons-material/AdminPanelSettingsRounded';
import ArrowForwardRounded from '@mui/icons-material/ArrowForwardRounded';
import BusinessRounded from '@mui/icons-material/BusinessRounded';
import DomainRounded from '@mui/icons-material/DomainRounded';
import EmailRounded from '@mui/icons-material/EmailRounded';
import LockRounded from '@mui/icons-material/LockRounded';
import ScienceRounded from '@mui/icons-material/ScienceRounded';
import ShieldRounded from '@mui/icons-material/ShieldRounded';
import VisibilityOffRounded from '@mui/icons-material/VisibilityOffRounded';
import VisibilityRounded from '@mui/icons-material/VisibilityRounded';

import axios from 'axios';

import {
  Alert,
  Box,
  Button,
  CircularProgress,
  IconButton,
  InputAdornment,
  TextField,
  ToggleButton,
  ToggleButtonGroup,
  Typography,
} from '@mui/material';

import {
  alpha,
} from '@mui/material/styles';

import {
  Link,
  useNavigate,
} from 'react-router-dom';

import AuthShell from '../components/auth/AuthShell';
import { useAuth } from '../contexts/AuthContext';
import {
  fetchPortalTenantContext,
} from '../features/portal-access/api/portalPublicApi';

type AccessMode =
  | 'laboratory'
  | 'platform';

interface LoginFormState {
  email: string;
  password: string;
  domain: string;
}

interface ApiErrorPayload {
  message?: string;
  errors?: Record<string, string[]>;
}

const initialFormState: LoginFormState = {
  email: '',
  password: '',
  domain: '',
};

const extractErrorMessage = (
  error: unknown
): string => {
  if (!axios.isAxiosError<ApiErrorPayload>(error)) {
    return 'We could not sign you in. Please try again.';
  }

  const responseData = error.response?.data;

  if (responseData?.message) {
    return responseData.message;
  }

  const firstValidationMessage = Object.values(
    responseData?.errors ?? {}
  )
    .flat()
    .find(Boolean);

  return (
    firstValidationMessage ??
    'We could not sign you in. Please verify your details.'
  );
};

const Login = () => {
  const navigate = useNavigate();
  const { login } = useAuth();

  const [accessMode, setAccessMode] =
    useState<AccessMode>('laboratory');

  const [form, setForm] =
    useState<LoginFormState>(initialFormState);

  const [showPassword, setShowPassword] =
    useState(false);

  const [submitting, setSubmitting] =
    useState(false);

  const [error, setError] =
    useState<string | null>(null);

  const [
    portalTenantName,
    setPortalTenantName,
  ] = useState<string | null>(null);

  const isLaboratoryAccess =
    accessMode === 'laboratory';

  const modeDescription = useMemo(() => {
    if (isLaboratoryAccess) {
      return 'Enter your laboratory domain and account credentials to continue.';
    }

    return 'Use your platform administrator credentials to access tenant operations.';
  }, [isLaboratoryAccess]);

  const normalizedDomain = useMemo(
    () => form.domain
      .trim()
      .toLowerCase(),
    [form.domain]
  );

  const patientPortalPath = useMemo(
    () => (
      normalizedDomain
        ? `/patient-portal/login?domain=${encodeURIComponent(
            normalizedDomain
          )}`
        : '/patient-portal/login'
    ),
    [normalizedDomain]
  );

  const clinicianPortalPath = useMemo(
    () => (
      normalizedDomain
        ? `/clinician-portal/login?domain=${encodeURIComponent(
            normalizedDomain
          )}`
        : '/clinician-portal/login'
    ),
    [normalizedDomain]
  );

  useEffect(() => {
    if (
      !isLaboratoryAccess
      || normalizedDomain.length < 2
    ) {
      setPortalTenantName(null);
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
              setPortalTenantName(
                tenant.name
              );
            }
          })
          .catch(() => {
            if (active) {
              setPortalTenantName(null);
            }
          });
      },
      350
    );

    return () => {
      active = false;
      window.clearTimeout(timer);
    };
  }, [
    isLaboratoryAccess,
    normalizedDomain,
  ]);

  const updateField = (
    field: keyof LoginFormState,
    value: string
  ) => {
    setForm((current) => ({
      ...current,
      [field]: value,
    }));

    if (error) {
      setError(null);
    }
  };

  const handleModeChange = (
    _event: React.MouseEvent<HTMLElement>,
    value: AccessMode | null
  ) => {
    if (!value) {
      return;
    }

    setAccessMode(value);
    setError(null);

    if (value === 'platform') {
      setForm((current) => ({
        ...current,
        domain: '',
      }));
    }
  };

  const handleSubmit = async (
    event: FormEvent<HTMLFormElement>
  ) => {
    event.preventDefault();
    setError(null);

    const email = form.email.trim();
    const domain = form.domain
      .trim()
      .toLowerCase();

    if (
      !email ||
      !form.password ||
      (isLaboratoryAccess && !domain)
    ) {
      setError(
        isLaboratoryAccess
          ? 'Enter your laboratory domain, email address and password.'
          : 'Enter your email address and password.'
      );

      return;
    }

    setSubmitting(true);

    try {
      const payload = {
        email,
        password: form.password,
        ...(isLaboratoryAccess
          ? { domain }
          : {}),
      } as Parameters<typeof login>[0];

      const response = await login(payload);

      navigate(
        response.is_superadmin
          ? '/landlord'
          : '/dashboard',
        {
          replace: true,
        }
      );
    } catch (caughtError) {
      setError(
        extractErrorMessage(caughtError)
      );
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <AuthShell
      eyebrow="Secure access"
      title="Welcome back."
      description={modeDescription}
    >
      <Box
        component="form"
        noValidate
        onSubmit={handleSubmit}
      >
        <ToggleButtonGroup
          exclusive
          fullWidth
          value={accessMode}
          onChange={handleModeChange}
          aria-label="Select access type"
          sx={{
            p: 0.6,
            mb: 3.25,
            gap: 0.5,
            borderRadius: 3.25,
            border: '1px solid',
            borderColor: 'divider',
            backgroundColor:
              alpha('#0B1220', 0.025),

            '& .MuiToggleButtonGroup-grouped': {
              minHeight: 54,
              m: 0,
              gap: 1.2,
              border: 0,
              borderRadius:
                '11px !important',
              color: 'text.secondary',
              fontSize: '0.78rem',
              fontWeight: 650,
              textTransform: 'none',
              transition:
                'color 180ms ease, background-color 180ms ease, box-shadow 180ms ease',

              '&.Mui-selected': {
                color: 'text.primary',
                backgroundColor:
                  'background.paper',
                boxShadow:
                  '0 6px 18px rgba(11, 18, 32, 0.08)',

                '&:hover': {
                  backgroundColor:
                    'background.paper',
                },
              },

              '&:hover': {
                backgroundColor:
                  alpha('#246BFD', 0.045),
              },
            },
          }}
        >
          <ToggleButton value="laboratory">
            <BusinessRounded
              sx={{
                fontSize: 19,
                color:
                  isLaboratoryAccess
                    ? 'primary.main'
                    : 'text.disabled',
              }}
            />
            Laboratory
          </ToggleButton>

          <ToggleButton value="platform">
            <AdminPanelSettingsRounded
              sx={{
                fontSize: 19,
                color:
                  !isLaboratoryAccess
                    ? 'secondary.main'
                    : 'text.disabled',
              }}
            />
            Platform admin
          </ToggleButton>
        </ToggleButtonGroup>

        {error && (
          <Alert
            severity="error"
            sx={{
              mb: 2.5,
            }}
          >
            {error}
          </Alert>
        )}

        <Box
          sx={{
            display: 'grid',
            gap: 2.15,
          }}
        >
          {isLaboratoryAccess && (
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
          )}

          <TextField
            fullWidth
            required
            autoFocus={!isLaboratoryAccess}
            label="Email address"
            type="email"
            placeholder="name@laboratory.com"
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
                          (visible) => !visible
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
        </Box>

        <Button
          fullWidth
          type="submit"
          variant="contained"
          size="large"
          disabled={submitting}
          endIcon={
            submitting
              ? undefined
              : <ArrowForwardRounded />
          }
          sx={{
            position: 'relative',
            mt: 3,
            minHeight: 52,
            overflow: 'hidden',
            background:
              isLaboratoryAccess
                ? 'linear-gradient(135deg, #246BFD 0%, #1D4ED8 100%)'
                : 'linear-gradient(135deg, #0E7490 0%, #155E75 100%)',

            '&::before': {
              content: '""',
              position: 'absolute',
              top: 0,
              left: '-110%',
              width: '70%',
              height: '100%',
              transform: 'skewX(-22deg)',
              background:
                'linear-gradient(90deg, transparent, rgba(255,255,255,0.22), transparent)',
              transition:
                'left 620ms ease',
            },

            '&:hover::before': {
              left: '140%',
            },
          }}
        >
          {submitting ? (
            <Box
              sx={{
                display: 'inline-flex',
                alignItems: 'center',
                gap: 1.25,
              }}
            >
              <CircularProgress
                size={18}
                thickness={4}
                sx={{
                  color: 'inherit',
                }}
              />
              Verifying access
            </Box>
          ) : isLaboratoryAccess ? (
            'Enter laboratory workspace'
          ) : (
            'Open platform control'
          )}
        </Button>

        <Box
          sx={{
            display: 'flex',
            alignItems: 'flex-start',
            gap: 1.25,
            mt: 2.5,
            px: 0.25,
          }}
        >
          <ShieldRounded
            sx={{
              flexShrink: 0,
              mt: 0.1,
              color: 'success.main',
              fontSize: 18,
            }}
          />

          <Typography
            sx={{
              color: 'text.secondary',
              fontSize: '0.73rem',
              lineHeight: 1.58,
            }}
          >
            Your session is protected and scoped to
            your authorised laboratory environment.
          </Typography>
        </Box>

        <Box
          sx={{
            mt: 3,
            p: 1.75,
            border: '1px solid',
            borderColor: 'divider',
            borderRadius: 3,
            backgroundColor:
              alpha('#246BFD', 0.025),
          }}
        >
          <Box
            sx={{
              display: 'flex',
              gap: 1.2,
              alignItems: 'flex-start',
            }}
          >
            <Box
              sx={{
                width: 36,
                height: 36,
                display: 'grid',
                placeItems: 'center',
                flexShrink: 0,
                borderRadius: '11px',
                color: 'primary.main',
                backgroundColor:
                  alpha('#246BFD', 0.09),
              }}
            >
              <ScienceRounded
                sx={{ fontSize: 20 }}
              />
            </Box>

            <Box sx={{ minWidth: 0 }}>
              <Typography
                sx={{
                  fontSize: '0.82rem',
                  fontWeight: 720,
                  lineHeight: 1.4,
                }}
              >
                {portalTenantName
                  ? `${portalTenantName} Portals`
                  : 'Patient and clinician portals'}
              </Typography>

              <Typography
                sx={{
                  mt: 0.35,
                  color: 'text.secondary',
                  fontSize: '0.72rem',
                  lineHeight: 1.55,
                }}
              >
                Patient and clinician access uses
                separate security sessions from
                laboratory staff access.
              </Typography>
            </Box>
          </Box>

          <Box
            sx={{
              display: 'grid',
              gridTemplateColumns: {
                xs: '1fr',
                sm: 'repeat(2, minmax(0, 1fr))',
              },
              gap: 1,
              mt: 1.5,
            }}
          >
            <Button
              component={Link}
              to={patientPortalPath}
              variant="outlined"
              size="small"
              sx={{
                minHeight: 40,
              }}
            >
              {portalTenantName
                ? `${portalTenantName} Patient Portal`
                : 'Patient sign in'}
            </Button>

            <Button
              component={Link}
              to={clinicianPortalPath}
              variant="outlined"
              size="small"
              sx={{
                minHeight: 40,
              }}
            >
              {portalTenantName
                ? `${portalTenantName} Clinician Portal`
                : 'Clinician sign in'}
            </Button>
          </Box>
        </Box>

        {isLaboratoryAccess && (
          <Box
            sx={{
              mt: 3,
              pt: 3,
              textAlign: 'center',
              borderTop: '1px solid',
              borderColor: 'divider',
            }}
          >
            <Typography
              sx={{
                color: 'text.secondary',
                fontSize: '0.8rem',
              }}
            >
              Setting up a new laboratory?{' '}
              <Typography
                component={Link}
                to="/register"
                sx={{
                  color: 'primary.main',
                  fontSize: 'inherit',
                  fontWeight: 680,
                  textDecoration: 'none',

                  '&:hover': {
                    textDecoration:
                      'underline',
                    textUnderlineOffset: 4,
                  },
                }}
              >
                Create a workspace
              </Typography>
            </Typography>
          </Box>
        )}
      </Box>
    </AuthShell>
  );
};

export default Login;