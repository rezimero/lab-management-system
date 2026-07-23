import EngineeringRounded from '@mui/icons-material/EngineeringRounded';
import LoginRounded from '@mui/icons-material/LoginRounded';
import SearchRounded from '@mui/icons-material/SearchRounded';
import ShieldRounded from '@mui/icons-material/ShieldRounded';

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
  InputAdornment,
  List,
  ListItemButton,
  ListItemText,
  TextField,
  Typography,
} from '@mui/material';

import {
  alpha,
} from '@mui/material/styles';

import {
  useMemo,
  useState,
} from 'react';

import toast from 'react-hot-toast';

import {
  useNavigate,
} from 'react-router-dom';

import api from '../../api/client';

import {
  useAuth,
} from '../../contexts/AuthContext';

interface TenantSummary {
  id: number;
  name: string;
  domain: string;
  is_active: boolean;
}

interface TenantListResponse {
  tenants:
    | TenantSummary[]
    | {
        data: TenantSummary[];
      };
}

const ImpersonateButton = () => {
  const navigate = useNavigate();

  const {
    startImpersonation,
  } = useAuth();

  const [
    open,
    setOpen,
  ] = useState(false);

  const [
    tenants,
    setTenants,
  ] = useState<TenantSummary[]>([]);

  const [
    search,
    setSearch,
  ] = useState('');

  const [
    loading,
    setLoading,
  ] = useState(false);

  const [
    enteringTenantId,
    setEnteringTenantId,
  ] = useState<number | null>(null);

  const [
    error,
    setError,
  ] = useState<string | null>(null);

  const filteredTenants =
    useMemo(() => {
      const query =
        search.trim().toLowerCase();

      if (!query) {
        return tenants;
      }

      return tenants.filter(
        (tenant) =>
          tenant.name
            .toLowerCase()
            .includes(query) ||
          tenant.domain
            .toLowerCase()
            .includes(query)
      );
    }, [search, tenants]);

  const fetchTenants =
    async (): Promise<void> => {
      setLoading(true);
      setError(null);

      try {
        const response =
          await api.get<TenantListResponse>(
            '/landlord/tenants'
          );

        const tenantPayload =
          response.data.tenants;

        const tenantList =
          Array.isArray(tenantPayload)
            ? tenantPayload
            : tenantPayload?.data ?? [];

        setTenants(tenantList);
      } catch {
        setError(
          'Rezhel could not load the tenant directory.'
        );
      } finally {
        setLoading(false);
      }
    };

  const handleOpen =
    async (): Promise<void> => {
      setOpen(true);
      setSearch('');

      await fetchTenants();
    };

  const handleImpersonate =
    async (
      tenant: TenantSummary
    ): Promise<void> => {
      setEnteringTenantId(tenant.id);
      setError(null);

      try {
        await startImpersonation(
          tenant.id
        );

        toast.success(
          `Maintenance access opened for ${tenant.name}.`
        );

        setOpen(false);

        navigate('/dashboard', {
          replace: true,
        });
      } catch (caughtError) {
        const message =
          caughtError instanceof Error
            ? caughtError.message
            : 'Rezhel could not open the tenant maintenance session.';

        setError(message);
      } finally {
        setEnteringTenantId(null);
      }
    };

  return (
    <>
      <Button
        variant="contained"
        startIcon={
          <EngineeringRounded />
        }
        onClick={() => {
          void handleOpen();
        }}
        sx={{
          color: '#FFFFFF',
          background:
            'linear-gradient(135deg, #7F56D9 0%, #6941C6 100%)',
          boxShadow:
            '0 10px 25px rgba(127,86,217,0.24)',

          '&:hover': {
            boxShadow:
              '0 14px 32px rgba(127,86,217,0.32)',
          },
        }}
      >
        Tenant maintenance
      </Button>

      <Dialog
        open={open}
        onClose={() => {
          if (!enteringTenantId) {
            setOpen(false);
          }
        }}
        maxWidth="sm"
        fullWidth
      >
        <DialogTitle
          sx={{
            pb: 1.25,
          }}
        >
          <Box
            sx={{
              display: 'flex',
              alignItems: 'center',
              gap: 1.4,
            }}
          >
            <Box
              sx={{
                width: 44,
                height: 44,
                display: 'grid',
                placeItems: 'center',
                borderRadius: 2.8,
                color: '#6941C6',
                backgroundColor:
                  alpha('#7F56D9', 0.09),
              }}
            >
              <ShieldRounded />
            </Box>

            <Box>
              <Typography
                sx={{
                  fontSize: '1rem',
                  fontWeight: 710,
                }}
              >
                Enter tenant maintenance mode
              </Typography>

              <Typography
                sx={{
                  mt: 0.25,
                  color: 'text.secondary',
                  fontSize: '0.7rem',
                }}
              >
                Open a secure tenant-scoped session
                for support and maintenance.
              </Typography>
            </Box>
          </Box>
        </DialogTitle>

        <DialogContent>
          {error && (
            <Alert
              severity="error"
              sx={{
                mb: 2,
              }}
            >
              {error}
            </Alert>
          )}

          <TextField
            fullWidth
            size="small"
            placeholder="Search laboratories or domains"
            value={search}
            onChange={(event) => {
              setSearch(
                event.target.value
              );
            }}
            slotProps={{
              input: {
                startAdornment: (
                  <InputAdornment position="start">
                    <SearchRounded
                      sx={{
                        color: 'text.disabled',
                      }}
                    />
                  </InputAdornment>
                ),
              },
            }}
            sx={{
              mb: 2,
            }}
          />

          {loading ? (
            <Box
              sx={{
                minHeight: 220,
                display: 'grid',
                placeItems: 'center',
              }}
            >
              <CircularProgress />
            </Box>
          ) : filteredTenants.length === 0 ? (
            <Box
              sx={{
                minHeight: 190,
                display: 'grid',
                placeItems: 'center',
                textAlign: 'center',
                px: 3,
              }}
            >
              <Box>
                <Typography
                  sx={{
                    fontWeight: 670,
                  }}
                >
                  No matching laboratories
                </Typography>

                <Typography
                  sx={{
                    mt: 0.5,
                    color: 'text.secondary',
                    fontSize: '0.75rem',
                  }}
                >
                  Try a different laboratory name
                  or domain.
                </Typography>
              </Box>
            </Box>
          ) : (
            <List
              disablePadding
              sx={{
                display: 'grid',
                gap: 0.75,
              }}
            >
              {filteredTenants.map(
                (tenant) => {
                  const entering =
                    enteringTenantId ===
                    tenant.id;

                  return (
                    <ListItemButton
                      key={tenant.id}
                      disabled={
                        !tenant.is_active ||
                        Boolean(
                          enteringTenantId
                        )
                      }
                      onClick={() => {
                        void handleImpersonate(
                          tenant
                        );
                      }}
                      sx={{
                        minHeight: 68,
                        px: 1.5,
                        borderRadius: 3,
                        border: '1px solid',
                        borderColor: 'divider',

                        '&:hover': {
                          borderColor:
                            alpha(
                              '#7F56D9',
                              0.22
                            ),
                          backgroundColor:
                            alpha(
                              '#7F56D9',
                              0.035
                            ),
                        },
                      }}
                    >
                      <ListItemText
                        primary={
                          tenant.name
                        }
                        secondary={
                          tenant.domain
                        }
                        slotProps={{
                          primary: {
                            sx: {
                              fontSize:
                                '0.8rem',
                              fontWeight:
                                660,
                            },
                          },

                          secondary: {
                            sx: {
                              mt: 0.25,
                              fontSize:
                                '0.67rem',
                            },
                          },
                        }}
                      />

                      {!tenant.is_active ? (
                        <Chip
                          label="Suspended"
                          size="small"
                          color="error"
                        />
                      ) : entering ? (
                        <CircularProgress
                          size={20}
                        />
                      ) : (
                        <Button
                          size="small"
                          variant="outlined"
                          startIcon={
                            <LoginRounded />
                          }
                          component="span"
                        >
                          Enter
                        </Button>
                      )}
                    </ListItemButton>
                  );
                }
              )}
            </List>
          )}
        </DialogContent>

        <DialogActions
          sx={{
            px: 3,
            pb: 2.5,
          }}
        >
          <Button
            variant="outlined"
            disabled={Boolean(
              enteringTenantId
            )}
            onClick={() => {
              setOpen(false);
            }}
          >
            Cancel
          </Button>
        </DialogActions>
      </Dialog>
    </>
  );
};

export default ImpersonateButton;