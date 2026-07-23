import {
  useEffect,
  useMemo,
  useState,
} from 'react';

import ApartmentRounded from '@mui/icons-material/ApartmentRounded';
import CloseRounded from '@mui/icons-material/CloseRounded';
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
  FormControlLabel,
  IconButton,
  InputAdornment,
  Switch,
  TextField,
  Typography,
  alpha,
} from '@mui/material';

export interface TenantRecord {
  id: number;
  name: string;
  domain: string;
  database?: string;
  is_active: boolean;
  trial_ends_at?: string | null;
  created_at?: string;

  stats?: {
    users?: number;
    patients?: number;
    lab_tests?: number;
    test_results?: number;
  };
}

export interface CreateTenantPayload {
  name: string;
  domain: string;
  admin_name: string;
  admin_email: string;
  admin_password: string;
}

export interface UpdateTenantPayload {
  name: string;
  domain: string;
  is_active: boolean;
}

interface TenantFormDialogProps {
  open: boolean;
  tenant?: TenantRecord | null;
  submitting: boolean;
  serverError?: string | null;

  onClose: () => void;

  onCreate: (
    payload: CreateTenantPayload
  ) => Promise<void>;

  onUpdate: (
    tenantId: number,
    payload: UpdateTenantPayload
  ) => Promise<void>;
}

interface FormState {
  name: string;
  domain: string;
  adminName: string;
  adminEmail: string;
  password: string;
  passwordConfirmation: string;
  isActive: boolean;
}

interface FormErrors {
  name?: string;
  domain?: string;
  adminName?: string;
  adminEmail?: string;
  password?: string;
  passwordConfirmation?: string;
}

const emptyForm: FormState = {
  name: '',
  domain: '',
  adminName: '',
  adminEmail: '',
  password: '',
  passwordConfirmation: '',
  isActive: true,
};

const normaliseDomain = (
  value: string
): string => {
  return value
    .trim()
    .toLowerCase()
    .replace(/\s+/g, '-')
    .replace(/[^a-z0-9-]/g, '')
    .replace(/-+/g, '-')
    .replace(/^-|-$/g, '');
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

const TenantFormDialog = ({
  open,
  tenant,
  submitting,
  serverError,
  onClose,
  onCreate,
  onUpdate,
}: TenantFormDialogProps) => {
  const editing = Boolean(tenant);

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

  useEffect(() => {
    if (!open) {
      return;
    }

    setErrors({});
    setShowPassword(false);

    if (tenant) {
      setForm({
        ...emptyForm,
        name: tenant.name,
        domain: tenant.domain,
        isActive: tenant.is_active,
      });

      return;
    }

    setForm(emptyForm);
  }, [open, tenant]);

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

  const updateForm = (
    key: keyof FormState,
    value: string | boolean
  ) => {
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

      if (!form.name.trim()) {
        nextErrors.name =
          'Laboratory name is required.';
      }

      if (
        form.name.trim().length > 255
      ) {
        nextErrors.name =
          'Laboratory name cannot exceed 255 characters.';
      }

      const domain =
        normaliseDomain(form.domain);

      if (!domain) {
        nextErrors.domain =
          'A tenant domain is required.';
      } else if (
        !/^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(
          domain
        )
      ) {
        nextErrors.domain =
          'Use lowercase letters, numbers and single hyphens only.';
      }

      if (!editing) {
        if (!form.adminName.trim()) {
          nextErrors.adminName =
            'Administrator name is required.';
        }

        if (
          !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(
            form.adminEmail.trim()
          )
        ) {
          nextErrors.adminEmail =
            'Enter a valid administrator email address.';
        }

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

      const domain =
        normaliseDomain(form.domain);

      if (tenant) {
        await onUpdate(tenant.id, {
          name: form.name.trim(),
          domain,
          is_active: form.isActive,
        });

        return;
      }

      await onCreate({
        name: form.name.trim(),
        domain,
        admin_name:
          form.adminName.trim(),
        admin_email:
          form.adminEmail
            .trim()
            .toLowerCase(),
        admin_password:
          form.password,
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
              top: -90,
              right: -80,
              width: 220,
              height: 220,
              borderRadius: '50%',
              background:
                'radial-gradient(circle, rgba(127,86,217,0.4), transparent 70%)',
            }}
          />

          <Box
            sx={{
              position: 'relative',
              width: 48,
              height: 48,
              display: 'grid',
              placeItems: 'center',
              flexShrink: 0,
              borderRadius: 3,
              color: '#DDD6FE',
              border:
                '1px solid rgba(196,181,253,0.13)',
              backgroundColor:
                'rgba(127,86,217,0.16)',
            }}
          >
            <ApartmentRounded />
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
                ? 'Edit laboratory tenant'
                : 'Provision a new laboratory'}
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
                ? 'Maintain the tenant identity and platform status.'
                : 'Create the tenant database, workspace and first administrator.'}
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
            label="Laboratory name"
            value={form.name}
            error={Boolean(errors.name)}
            helperText={
              errors.name ??
              'The official name displayed throughout Rezhel.'
            }
            onChange={(event) => {
              updateForm(
                'name',
                event.target.value
              );
            }}
          />

          <TextField
            fullWidth
            required
            label="Tenant domain"
            value={form.domain}
            error={Boolean(
              errors.domain
            )}
            helperText={
              errors.domain ??
              'Example: development-laboratory'
            }
            onChange={(event) => {
              updateForm(
                'domain',
                normaliseDomain(
                  event.target.value
                )
              );
            }}
          />
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
                Initial administrator
              </Typography>

              <Typography
                sx={{
                  mt: 0.45,
                  color: 'text.secondary',
                  fontSize: '0.74rem',
                  lineHeight: 1.6,
                }}
              >
                This account becomes the first
                active administrator in the new
                laboratory workspace.
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
                label="Administrator name"
                value={form.adminName}
                error={Boolean(
                  errors.adminName
                )}
                helperText={
                  errors.adminName ??
                  'Full professional name.'
                }
                onChange={(event) => {
                  updateForm(
                    'adminName',
                    event.target.value
                  );
                }}
              />

              <TextField
                fullWidth
                required
                type="email"
                label="Administrator email"
                value={form.adminEmail}
                error={Boolean(
                  errors.adminEmail
                )}
                helperText={
                  errors.adminEmail ??
                  'Used to sign in to this laboratory.'
                }
                onChange={(event) => {
                  updateForm(
                    'adminEmail',
                    event.target.value
                  );
                }}
              />

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
                  updateForm(
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
                  'Enter the same temporary password again.'
                }
                onChange={(event) => {
                  updateForm(
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
                  alignItems: 'center',
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
                        transition:
                          'background-color 180ms ease',
                      }}
                    />
                  )
                )}
              </Box>

              <Typography
                sx={{
                  mt: 0.9,
                  color: 'text.secondary',
                  fontSize: '0.65rem',
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

        {editing && (
          <Box
            sx={{
              mt: 3,
              p: 2,
              borderRadius: 3,
              border: '1px solid',
              borderColor: 'divider',
              backgroundColor:
                alpha('#0B1220', 0.018),
            }}
          >
            <FormControlLabel
              control={
                <Switch
                  checked={form.isActive}
                  onChange={(event) => {
                    updateForm(
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
                      fontSize: '0.78rem',
                      fontWeight: 650,
                    }}
                  >
                    Tenant active
                  </Typography>

                  <Typography
                    sx={{
                      mt: 0.2,
                      color: 'text.secondary',
                      fontSize: '0.65rem',
                    }}
                  >
                    Inactive tenants cannot operate
                    normally until reactivated.
                  </Typography>
                </Box>
              }
            />
          </Box>
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
            ) : (
              <ApartmentRounded />
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
              ? 'Saving changes'
              : 'Provisioning tenant'
            : editing
              ? 'Save tenant'
              : 'Create laboratory'}
        </Button>
      </DialogActions>
    </Dialog>
  );
};

export default TenantFormDialog;