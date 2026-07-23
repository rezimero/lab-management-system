import {
  useEffect,
  useMemo,
  useState,
} from 'react';

import AccountCircleRounded from '@mui/icons-material/AccountCircleRounded';
import CloseRounded from '@mui/icons-material/CloseRounded';
import EmailRounded from '@mui/icons-material/EmailRounded';
import PersonAddAlt1Rounded from '@mui/icons-material/PersonAddAlt1Rounded';
import SaveRounded from '@mui/icons-material/SaveRounded';
import ShieldRounded from '@mui/icons-material/ShieldRounded';
import VisibilityOffRounded from '@mui/icons-material/VisibilityOffRounded';
import VisibilityRounded from '@mui/icons-material/VisibilityRounded';

import {
  Alert,
  Box,
  Button,
  Chip,
  CircularProgress,
  Dialog,
  DialogActions,
  DialogContent,
  DialogTitle,
  FormControlLabel,
  IconButton,
  InputAdornment,
  MenuItem,
  Switch,
  TextField,
  Typography,
  alpha,
} from '@mui/material';

export interface PlatformTenantOption {
  id: number;
  name: string;
  domain: string;
  is_active: boolean;
}

export interface PlatformUserRecord {
  user_key: string;
  id: number;
  tenant_id: number;
  tenant_name: string;
  tenant_domain: string;
  name: string;
  email: string;
  role: string;
  is_active: boolean;
  email_verified: boolean;
  email_verified_at?: string | null;
  created_at?: string | null;
  updated_at?: string | null;
}

export interface CreatePlatformUserPayload {
  tenant_id: number;
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
  role: string;
  is_active: boolean;
  email_verified: boolean;
}

export interface UpdatePlatformUserPayload {
  tenant_id: number;
  name: string;
  email: string;
  role: string;
  is_active: boolean;
  email_verified: boolean;
}

interface PlatformUserDialogProps {
  open: boolean;
  user?: PlatformUserRecord | null;
  tenants: PlatformTenantOption[];
  roles: Record<string, string>;
  submitting: boolean;
  serverError?: string | null;

  onClose: () => void;

  onCreate: (
    payload: CreatePlatformUserPayload
  ) => Promise<void>;

  onUpdate: (
    user: PlatformUserRecord,
    payload: UpdatePlatformUserPayload
  ) => Promise<void>;
}

interface FormState {
  tenantId: string;
  name: string;
  email: string;
  password: string;
  passwordConfirmation: string;
  role: string;
  isActive: boolean;
  emailVerified: boolean;
}

interface FormErrors {
  tenantId?: string;
  name?: string;
  email?: string;
  password?: string;
  passwordConfirmation?: string;
  role?: string;
}

const emptyForm: FormState = {
  tenantId: '',
  name: '',
  email: '',
  password: '',
  passwordConfirmation: '',
  role: '',
  isActive: true,
  emailVerified: false,
};

const validatePassword = (
  password: string
): string | undefined => {
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

  return undefined;
};

const PlatformUserDialog = ({
  open,
  user,
  tenants,
  roles,
  submitting,
  serverError,
  onClose,
  onCreate,
  onUpdate,
}: PlatformUserDialogProps) => {
  const editing = Boolean(user);

  const [
    form,
    setForm,
  ] = useState<FormState>(emptyForm);

  const [
    errors,
    setErrors,
  ] = useState<FormErrors>({});

  const [
    showPassword,
    setShowPassword,
  ] = useState(false);

  const roleEntries =
    useMemo(
      () => Object.entries(roles),
      [roles]
    );

  const activeTenants =
    useMemo(
      () =>
        tenants.filter(
          (tenant) =>
            tenant.is_active ||
            tenant.id === user?.tenant_id
        ),
      [tenants, user]
    );

  useEffect(() => {
    if (!open) {
      return;
    }

    setErrors({});
    setShowPassword(false);

    if (user) {
      setForm({
        tenantId: String(user.tenant_id),
        name: user.name,
        email: user.email,
        password: '',
        passwordConfirmation: '',
        role: user.role,
        isActive: user.is_active,
        emailVerified:
          user.email_verified,
      });

      return;
    }

    setForm({
      ...emptyForm,
      tenantId:
        activeTenants.length === 1
          ? String(activeTenants[0].id)
          : '',
      role:
        roleEntries[0]?.[0] ?? '',
    });
  }, [
    activeTenants,
    open,
    roleEntries,
    user,
  ]);

  const passwordStrength =
    useMemo(() => {
      if (!form.password) {
        return 0;
      }

      let score = 0;

      if (form.password.length >= 12) {
        score += 1;
      }

      if (/[a-z]/.test(form.password)) {
        score += 1;
      }

      if (/[A-Z]/.test(form.password)) {
        score += 1;
      }

      if (/[0-9]/.test(form.password)) {
        score += 1;
      }

      if (/[^A-Za-z0-9]/.test(form.password)) {
        score += 1;
      }

      return score;
    }, [form.password]);

  const updateField = (
    key: keyof FormState,
    value: string | boolean
  ): void => {
    setForm((current) => ({
      ...current,
      [key]: value,
    }));

    setErrors((current) => ({
      ...current,
      [key]: undefined,
    }));
  };

  const validate =
    (): FormErrors => {
      const nextErrors: FormErrors = {};

      const tenantId = Number(
        form.tenantId
      );

      if (
        !Number.isInteger(tenantId) ||
        tenantId <= 0
      ) {
        nextErrors.tenantId =
          'Select a laboratory tenant.';
      }

      if (!form.name.trim()) {
        nextErrors.name =
          'Full name is required.';
      } else if (
        form.name.trim().length > 255
      ) {
        nextErrors.name =
          'Full name cannot exceed 255 characters.';
      }

      if (
        !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(
          form.email.trim()
        )
      ) {
        nextErrors.email =
          'Enter a valid email address.';
      }

      if (!form.role) {
        nextErrors.role =
          'Select an account role.';
      }

      if (!editing) {
        const passwordError =
          validatePassword(form.password);

        if (passwordError) {
          nextErrors.password =
            passwordError;
        }

        if (
          form.password !==
          form.passwordConfirmation
        ) {
          nextErrors.passwordConfirmation =
            'The passwords do not match.';
        }
      }

      return nextErrors;
    };

  const handleSubmit =
    async (): Promise<void> => {
      const validationErrors =
        validate();

      setErrors(validationErrors);

      if (
        Object.keys(validationErrors)
          .length > 0
      ) {
        return;
      }

      const tenantId =
        Number(form.tenantId);

      if (user) {
        await onUpdate(user, {
          tenant_id: tenantId,
          name: form.name.trim(),
          email: form.email
            .trim()
            .toLowerCase(),
          role: form.role,
          is_active: form.isActive,
          email_verified:
            form.emailVerified,
        });

        return;
      }

      await onCreate({
        tenant_id: tenantId,
        name: form.name.trim(),
        email: form.email
          .trim()
          .toLowerCase(),
        password: form.password,
        password_confirmation:
          form.passwordConfirmation,
        role: form.role,
        is_active: form.isActive,
        email_verified:
          form.emailVerified,
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
      maxWidth="md"
      fullWidth
      slotProps={{
        paper: {
          sx: {
            overflow: 'hidden',
            borderRadius: 4.5,
          },
        },
      }}
    >
      <DialogTitle
        sx={{
          p: 0,
        }}
      >
        <Box
          sx={{
            position: 'relative',
            display: 'flex',
            alignItems: 'center',
            gap: 1.5,
            px: {
              xs: 2.5,
              sm: 3.5,
            },
            py: 2.75,
            color: '#FFFFFF',
            background:
              'linear-gradient(135deg, #07111F 0%, #171334 52%, #0B3042 100%)',
          }}
        >
          <Box
            aria-hidden="true"
            sx={{
              position: 'absolute',
              top: -100,
              right: -75,
              width: 230,
              height: 230,
              borderRadius: '50%',
              background:
                'radial-gradient(circle, rgba(127,86,217,0.42), transparent 70%)',
            }}
          />

          <Box
            sx={{
              position: 'relative',
              width: 48,
              height: 48,
              flexShrink: 0,
              display: 'grid',
              placeItems: 'center',
              borderRadius: 3,
              color: '#DDD6FE',
              border:
                '1px solid rgba(196,181,253,0.13)',
              backgroundColor:
                'rgba(127,86,217,0.16)',
            }}
          >
            {editing ? (
              <AccountCircleRounded />
            ) : (
              <PersonAddAlt1Rounded />
            )}
          </Box>

          <Box
            sx={{
              position: 'relative',
              minWidth: 0,
              flex: 1,
            }}
          >
            <Typography
              sx={{
                fontSize: '1.02rem',
                fontWeight: 720,
              }}
            >
              {editing
                ? 'Edit tenant user account'
                : 'Create tenant user account'}
            </Typography>

            <Typography
              sx={{
                mt: 0.35,
                color:
                  'rgba(255,255,255,0.48)',
                fontSize: '0.7rem',
                lineHeight: 1.5,
              }}
            >
              {editing
                ? `${user?.tenant_name} · ${user?.tenant_domain}`
                : 'Provision a user inside a selected laboratory tenant.'}
            </Typography>
          </Box>

          <IconButton
            aria-label="Close"
            disabled={submitting}
            onClick={onClose}
            sx={{
              position: 'relative',
              color:
                'rgba(255,255,255,0.65)',

              '&:hover': {
                color: '#FFFFFF',
                backgroundColor:
                  'rgba(255,255,255,0.08)',
              },
            }}
          >
            <CloseRounded />
          </IconButton>
        </Box>
      </DialogTitle>

      <DialogContent
        sx={{
          p: {
            xs: 2.5,
            sm: 3.5,
          },
        }}
      >
        {serverError && (
          <Alert
            severity="error"
            sx={{
              mb: 2.5,
            }}
          >
            {serverError}
          </Alert>
        )}

        <Box
          sx={{
            display: 'grid',
            gridTemplateColumns: {
              xs: '1fr',
              md: 'repeat(2, minmax(0, 1fr))',
            },
            gap: 2.25,
          }}
        >
          <TextField
            fullWidth
            required
            select
            disabled={editing}
            label="Laboratory tenant"
            value={form.tenantId}
            error={Boolean(
              errors.tenantId
            )}
            helperText={
              errors.tenantId ??
              (
                editing
                  ? 'A user cannot be moved between tenant databases.'
                  : 'Select where this user account will be created.'
              )
            }
            onChange={(event) => {
              updateField(
                'tenantId',
                event.target.value
              );
            }}
          >
            {activeTenants.map(
              (tenant) => (
                <MenuItem
                  key={tenant.id}
                  value={String(
                    tenant.id
                  )}
                >
                  <Box
                    sx={{
                      display: 'flex',
                      alignItems: 'center',
                      justifyContent:
                        'space-between',
                      width: '100%',
                      gap: 2,
                    }}
                  >
                    <Box>
                      <Typography
                        sx={{
                          fontSize: '0.76rem',
                          fontWeight: 650,
                        }}
                      >
                        {tenant.name}
                      </Typography>

                      <Typography
                        sx={{
                          mt: 0.15,
                          color:
                            'text.secondary',
                          fontSize: '0.62rem',
                        }}
                      >
                        {tenant.domain}
                      </Typography>
                    </Box>

                    {!tenant.is_active && (
                      <Chip
                        label="Suspended"
                        color="error"
                        size="small"
                      />
                    )}
                  </Box>
                </MenuItem>
              )
            )}
          </TextField>

          <TextField
            fullWidth
            required
            label="Full name"
            value={form.name}
            error={Boolean(errors.name)}
            helperText={
              errors.name ??
              'The user’s professional display name.'
            }
            onChange={(event) => {
              updateField(
                'name',
                event.target.value
              );
            }}
            slotProps={{
              input: {
                startAdornment: (
                  <InputAdornment position="start">
                    <AccountCircleRounded />
                  </InputAdornment>
                ),
              },
            }}
          />

          <TextField
            fullWidth
            required
            type="email"
            label="Email address"
            value={form.email}
            error={Boolean(errors.email)}
            helperText={
              errors.email ??
              'Used for authentication inside the selected tenant.'
            }
            onChange={(event) => {
              updateField(
                'email',
                event.target.value
              );
            }}
            slotProps={{
              input: {
                startAdornment: (
                  <InputAdornment position="start">
                    <EmailRounded />
                  </InputAdornment>
                ),
              },
            }}
          />

          <TextField
            fullWidth
            required
            select
            label="Account role"
            value={form.role}
            error={Boolean(errors.role)}
            helperText={
              errors.role ??
              'Determines operational permissions inside the laboratory.'
            }
            onChange={(event) => {
              updateField(
                'role',
                event.target.value
              );
            }}
          >
            {roleEntries.map(
              ([role, description]) => (
                <MenuItem
                  key={role}
                  value={role}
                >
                  <Box>
                    <Typography
                      sx={{
                        fontSize: '0.76rem',
                        fontWeight: 650,
                        textTransform:
                          'capitalize',
                      }}
                    >
                      {role.replaceAll(
                        '_',
                        ' '
                      )}
                    </Typography>

                    <Typography
                      sx={{
                        mt: 0.15,
                        color:
                          'text.secondary',
                        fontSize: '0.61rem',
                      }}
                    >
                      {description}
                    </Typography>
                  </Box>
                </MenuItem>
              )
            )}
          </TextField>
        </Box>

        {!editing && (
          <>
            <Box
              sx={{
                mt: 3.5,
                mb: 2,
              }}
            >
              <Typography
                variant="overline"
                sx={{
                  color: '#6941C6',
                }}
              >
                Temporary credentials
              </Typography>

              <Typography
                sx={{
                  mt: 0.4,
                  color: 'text.secondary',
                  fontSize: '0.73rem',
                  lineHeight: 1.6,
                }}
              >
                Create a strong temporary password
                and communicate it through an
                approved secure channel.
              </Typography>
            </Box>

            <Box
              sx={{
                display: 'grid',
                gridTemplateColumns: {
                  xs: '1fr',
                  md: 'repeat(2, minmax(0, 1fr))',
                },
                gap: 2.25,
              }}
            >
              <TextField
                fullWidth
                required
                label="Temporary password"
                type={
                  showPassword
                    ? 'text'
                    : 'password'
                }
                value={form.password}
                error={Boolean(
                  errors.password
                )}
                helperText={
                  errors.password ??
                  'Minimum 12 characters with uppercase, lowercase, number and symbol.'
                }
                onChange={(event) => {
                  updateField(
                    'password',
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
                value={
                  form.passwordConfirmation
                }
                error={Boolean(
                  errors.passwordConfirmation
                )}
                helperText={
                  errors.passwordConfirmation ??
                  'Enter the same password again.'
                }
                onChange={(event) => {
                  updateField(
                    'passwordConfirmation',
                    event.target.value
                  );
                }}
              />
            </Box>

            <Box
              sx={{
                mt: 2,
                p: 1.75,
                borderRadius: 3,
                border: '1px solid',
                borderColor: 'divider',
                backgroundColor:
                  alpha('#7F56D9', 0.025),
              }}
            >
              <Box
                sx={{
                  display: 'flex',
                  gap: 0.65,
                }}
              >
                {[1, 2, 3, 4, 5].map(
                  (level) => (
                    <Box
                      key={level}
                      sx={{
                        height: 5,
                        flex: 1,
                        borderRadius: 999,
                        backgroundColor:
                          passwordStrength >=
                          level
                            ? passwordStrength >=
                              5
                              ? 'success.main'
                              : '#7F56D9'
                            : alpha(
                                '#0B1220',
                                0.08
                              ),
                      }}
                    />
                  )
                )}
              </Box>

              <Typography
                sx={{
                  mt: 0.9,
                  color: 'text.secondary',
                  fontSize: '0.64rem',
                }}
              >
                Password strength:{' '}
                {passwordStrength >= 5
                  ? 'Strong'
                  : passwordStrength >= 3
                    ? 'Moderate'
                    : 'Needs improvement'}
              </Typography>
            </Box>
          </>
        )}

        <Box
          sx={{
            display: 'grid',
            gridTemplateColumns: {
              xs: '1fr',
              md: 'repeat(2, minmax(0, 1fr))',
            },
            gap: 1.5,
            mt: 3,
          }}
        >
          <Box
            sx={{
              p: 2,
              borderRadius: 3,
              border: '1px solid',
              borderColor: 'divider',
              backgroundColor:
                alpha('#079455', 0.025),
            }}
          >
            <FormControlLabel
              control={
                <Switch
                  checked={form.isActive}
                  onChange={(event) => {
                    updateField(
                      'isActive',
                      event.target.checked
                    );
                  }}
                />
              }
              label={
                <Box>
                  <Typography
                    sx={{
                      fontSize: '0.77rem',
                      fontWeight: 650,
                    }}
                  >
                    Account active
                  </Typography>

                  <Typography
                    sx={{
                      mt: 0.2,
                      color: 'text.secondary',
                      fontSize: '0.63rem',
                    }}
                  >
                    Suspended users cannot operate
                    inside the tenant.
                  </Typography>
                </Box>
              }
            />
          </Box>

          <Box
            sx={{
              p: 2,
              borderRadius: 3,
              border: '1px solid',
              borderColor: 'divider',
              backgroundColor:
                alpha('#246BFD', 0.025),
            }}
          >
            <FormControlLabel
              control={
                <Switch
                  checked={
                    form.emailVerified
                  }
                  onChange={(event) => {
                    updateField(
                      'emailVerified',
                      event.target.checked
                    );
                  }}
                />
              }
              label={
                <Box>
                  <Typography
                    sx={{
                      fontSize: '0.77rem',
                      fontWeight: 650,
                    }}
                  >
                    Email verified
                  </Typography>

                  <Typography
                    sx={{
                      mt: 0.2,
                      color: 'text.secondary',
                      fontSize: '0.63rem',
                    }}
                  >
                    Mark only after the user’s email
                    ownership has been confirmed.
                  </Typography>
                </Box>
              }
            />
          </Box>
        </Box>

        {editing && (
          <Alert
            severity="info"
            icon={<ShieldRounded />}
            sx={{
              mt: 2.5,
            }}
          >
            Changing email, role or account status
            revokes the user’s existing sessions.
          </Alert>
        )}
      </DialogContent>

      <DialogActions
        sx={{
          px: {
            xs: 2.5,
            sm: 3.5,
          },
          py: 2.5,
          borderTop: '1px solid',
          borderColor: 'divider',
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
          disabled={submitting}
          startIcon={
            submitting ? (
              <CircularProgress
                size={16}
                color="inherit"
              />
            ) : editing ? (
              <SaveRounded />
            ) : (
              <PersonAddAlt1Rounded />
            )
          }
          onClick={() => {
            void handleSubmit();
          }}
          sx={{
            minWidth: 170,
            background:
              'linear-gradient(135deg, #7F56D9, #6941C6)',
          }}
        >
          {submitting
            ? editing
              ? 'Saving account'
              : 'Creating account'
            : editing
              ? 'Save account'
              : 'Create user'}
        </Button>
      </DialogActions>
    </Dialog>
  );
};

export default PlatformUserDialog;