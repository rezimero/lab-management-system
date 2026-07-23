import {
  MouseEvent,
  useMemo,
  useState,
} from 'react';

import AccountCircleRounded from '@mui/icons-material/AccountCircleRounded';
import AddRounded from '@mui/icons-material/AddRounded';
import ApartmentRounded from '@mui/icons-material/ApartmentRounded';
import DeleteOutlineRounded from '@mui/icons-material/DeleteOutlineRounded';
import EditRounded from '@mui/icons-material/EditRounded';
import EmailRounded from '@mui/icons-material/EmailRounded';
import GroupsRounded from '@mui/icons-material/GroupsRounded';
import LockResetRounded from '@mui/icons-material/LockResetRounded';
import MoreHorizRounded from '@mui/icons-material/MoreHorizRounded';
import RefreshRounded from '@mui/icons-material/RefreshRounded';
import SearchRounded from '@mui/icons-material/SearchRounded';
import ShieldRounded from '@mui/icons-material/ShieldRounded';
import VerifiedRounded from '@mui/icons-material/VerifiedRounded';

import {
  Alert,
  Avatar,
  Box,
  Button,
  Card,
  CardContent,
  Chip,
  CircularProgress,
  Dialog,
  DialogActions,
  DialogContent,
  DialogTitle,
  IconButton,
  InputAdornment,
  Menu,
  MenuItem,
  Paper,
  Skeleton,
  TablePagination,
  TextField,
  Tooltip,
  Typography,
  alpha,
} from '@mui/material';

import {
  useMutation,
  useQuery,
  useQueryClient,
} from '@tanstack/react-query';

import axios from 'axios';
import toast from 'react-hot-toast';

import api from '../../api/client';

import PlatformUserDialog, {
  CreatePlatformUserPayload,
  PlatformTenantOption,
  PlatformUserRecord,
  UpdatePlatformUserPayload,
} from './PlatformUserDialog';

import PlatformUserPasswordDialog, {
  PlatformPasswordPayload,
} from './PlatformUserPasswordDialog';

interface UserIndexResponse {
  users: {
    data: PlatformUserRecord[];
    total: number;
    per_page: number;
    current_page: number;
    last_page: number;
  };

  roles: Record<string, string>;
  total_tenants: number;
}

interface TenantIndexResponse {
  tenants:
    | PlatformTenantOption[]
    | {
        data: PlatformTenantOption[];
      };
}

const getErrorMessage = (
  error: unknown
): string => {
  if (axios.isAxiosError(error)) {
    const data =
      error.response?.data as
        | {
            message?: string;
            errors?: Record<
              string,
              string[]
            >;
          }
        | undefined;

    if (data?.errors) {
      const validationError =
        Object.values(data.errors)
          .flat()
          .find(Boolean);

      if (validationError) {
        return validationError;
      }
    }

    if (data?.message) {
      return data.message;
    }
  }

  if (error instanceof Error) {
    return error.message;
  }

  return 'The account operation could not be completed.';
};

const roleLabel = (
  role: string
): string => {
  return role
    .replaceAll('_', ' ')
    .replace(/\b\w/g, (letter) =>
      letter.toUpperCase()
    );
};

const getInitials = (
  name: string
): string => {
  return name
    .trim()
    .split(/\s+/)
    .map((part) => part[0])
    .join('')
    .toUpperCase()
    .slice(0, 2);
};

const AllUsers = () => {
  const queryClient =
    useQueryClient();

  const [
    page,
    setPage,
  ] = useState(0);

  const [
    rowsPerPage,
    setRowsPerPage,
  ] = useState(25);

  const [
    search,
    setSearch,
  ] = useState('');

  const [
    tenantFilter,
    setTenantFilter,
  ] = useState('');

  const [
    editorOpen,
    setEditorOpen,
  ] = useState(false);

  const [
    editingUser,
    setEditingUser,
  ] = useState<PlatformUserRecord | null>(
    null
  );

  const [
    passwordUser,
    setPasswordUser,
  ] = useState<PlatformUserRecord | null>(
    null
  );

  const [
    deletingUser,
    setDeletingUser,
  ] = useState<PlatformUserRecord | null>(
    null
  );

  const [
    deleteConfirmation,
    setDeleteConfirmation,
  ] = useState('');

  const [
    editorError,
    setEditorError,
  ] = useState<string | null>(null);

  const [
    passwordError,
    setPasswordError,
  ] = useState<string | null>(null);

  const [
    menuAnchor,
    setMenuAnchor,
  ] = useState<HTMLElement | null>(
    null
  );

  const [
    menuUser,
    setMenuUser,
  ] = useState<PlatformUserRecord | null>(
    null
  );

  const {
    data,
    isPending,
    isError,
    isFetching,
    refetch,
  } = useQuery({
    queryKey: [
      'rezhel-landlord-users',
      page,
      rowsPerPage,
      search,
      tenantFilter,
    ],

    queryFn: async () => {
      const response =
        await api.get<UserIndexResponse>(
          '/landlord/users',
          {
            params: {
              page: page + 1,
              per_page: rowsPerPage,
              search:
                search.trim() || undefined,
              tenant_id:
                tenantFilter || undefined,
            },
          }
        );

      return response.data;
    },

    staleTime: 20_000,
  });

  const {
    data: tenants = [],
  } = useQuery({
    queryKey: [
      'rezhel-landlord-tenants',
    ],

    queryFn: async () => {
      const response =
        await api.get<TenantIndexResponse>(
          '/landlord/tenants'
        );

      const payload =
        response.data.tenants;

      return Array.isArray(payload)
        ? payload
        : payload?.data ?? [];
    },

    staleTime: 30_000,
  });

  const refreshUsers =
    async (): Promise<void> => {
      await queryClient.invalidateQueries({
        queryKey: [
          'rezhel-landlord-users',
        ],
      });

      await queryClient.invalidateQueries({
        queryKey: [
          'rezhel-landlord-dashboard',
        ],
      });
    };

  const createMutation =
    useMutation({
      mutationFn: async (
        payload: CreatePlatformUserPayload
      ) => {
        return api.post(
          '/landlord/users',
          payload
        );
      },

      onSuccess: async () => {
        toast.success(
          'Tenant user account created.'
        );

        setEditorOpen(false);
        setEditingUser(null);
        setEditorError(null);

        await refreshUsers();
      },

      onError: (error) => {
        setEditorError(
          getErrorMessage(error)
        );
      },
    });

  const updateMutation =
    useMutation({
      mutationFn: async ({
        user,
        payload,
      }: {
        user: PlatformUserRecord;
        payload: UpdatePlatformUserPayload;
      }) => {
        return api.put(
          `/landlord/users/${user.id}`,
          payload
        );
      },

      onSuccess: async (
        response
      ) => {
        const sessionsRevoked =
          Boolean(
            response.data
              ?.sessions_revoked
          );

        toast.success(
          sessionsRevoked
            ? 'User account updated and existing sessions revoked.'
            : 'User account updated.'
        );

        setEditorOpen(false);
        setEditingUser(null);
        setEditorError(null);

        await refreshUsers();
      },

      onError: (error) => {
        setEditorError(
          getErrorMessage(error)
        );
      },
    });

  const passwordMutation =
    useMutation({
      mutationFn: async ({
        user,
        payload,
      }: {
        user: PlatformUserRecord;
        payload: PlatformPasswordPayload;
      }) => {
        return api.post(
          `/landlord/users/${user.id}/reset-password`,
          payload
        );
      },

      onSuccess: async () => {
        toast.success(
          'Password reset and existing sessions revoked.'
        );

        setPasswordUser(null);
        setPasswordError(null);

        await refreshUsers();
      },

      onError: (error) => {
        setPasswordError(
          getErrorMessage(error)
        );
      },
    });

  const deleteMutation =
    useMutation({
      mutationFn: async (
        user: PlatformUserRecord
      ) => {
        return api.delete(
          `/landlord/users/${user.id}`,
          {
            data: {
              tenant_id:
                user.tenant_id,
            },
          }
        );
      },

      onSuccess: async () => {
        toast.success(
          'Tenant user account deleted.'
        );

        setDeletingUser(null);
        setDeleteConfirmation('');

        await refreshUsers();
      },

      onError: (error) => {
        toast.error(
          getErrorMessage(error)
        );
      },
    });

  const users =
    data?.users.data ?? [];

  const total =
    data?.users.total ?? 0;

  const roles =
    data?.roles ?? {};

  const summary =
    useMemo(() => {
      return users.reduce(
        (
          result,
          user
        ) => {
          result.active +=
            user.is_active ? 1 : 0;

          result.suspended +=
            user.is_active ? 0 : 1;

          result.admins +=
            user.role === 'admin'
              ? 1
              : 0;

          result.verified +=
            user.email_verified
              ? 1
              : 0;

          return result;
        },
        {
          active: 0,
          suspended: 0,
          admins: 0,
          verified: 0,
        }
      );
    }, [users]);

  const closeMenu = (): void => {
    setMenuAnchor(null);
    setMenuUser(null);
  };

  const openMenu = (
    event: MouseEvent<HTMLElement>,
    user: PlatformUserRecord
  ): void => {
    setMenuAnchor(
      event.currentTarget
    );

    setMenuUser(user);
  };

  const openCreate = (): void => {
    setEditingUser(null);
    setEditorError(null);
    setEditorOpen(true);
  };

  const openEdit = (
    user: PlatformUserRecord
  ): void => {
    closeMenu();

    setEditingUser(user);
    setEditorError(null);
    setEditorOpen(true);
  };

  const openPasswordReset = (
    user: PlatformUserRecord
  ): void => {
    closeMenu();

    setPasswordUser(user);
    setPasswordError(null);
  };

  const openDelete = (
    user: PlatformUserRecord
  ): void => {
    closeMenu();

    setDeleteConfirmation('');
    setDeletingUser(user);
  };

  if (isPending) {
    return (
      <Box
        sx={{
          display: 'grid',
          gap: 2.5,
        }}
      >
        <Skeleton
          variant="rounded"
          height={210}
          sx={{
            borderRadius: 5,
          }}
        />

        {[0, 1, 2, 3].map(
          (item) => (
            <Skeleton
              key={item}
              variant="rounded"
              height={100}
              sx={{
                borderRadius: 3.5,
              }}
            />
          )
        )}
      </Box>
    );
  }

  if (isError || !data) {
    return (
      <Box
        sx={{
          minHeight: 480,
          display: 'grid',
          placeItems: 'center',
        }}
      >
        <Alert
          severity="error"
          action={
            <Button
              color="inherit"
              startIcon={
                <RefreshRounded />
              }
              onClick={() => {
                void refetch();
              }}
            >
              Retry
            </Button>
          }
          sx={{
            width: '100%',
            maxWidth: 760,
          }}
        >
          Rezhel could not load the platform user
          directory.
        </Alert>
      </Box>
    );
  }

  const deleteConfirmed =
    deleteConfirmation ===
    deletingUser?.email;

  return (
    <Box
      sx={{
        display: 'grid',
        gap: 3,
      }}
    >
      <Card
        sx={{
          overflow: 'hidden',
          color: '#FFFFFF',
          border: 0,
          borderRadius: 5,
          background:
            'linear-gradient(135deg, #07111F 0%, #171334 50%, #0B3042 100%)',
          boxShadow:
            '0 26px 70px rgba(7,17,31,0.18)',
        }}
      >
        <CardContent
          sx={{
            display: 'grid',
            gridTemplateColumns: {
              xs: '1fr',
              lg: 'minmax(0,1fr) auto',
            },
            alignItems: 'center',
            gap: 3,
            p: {
              xs: 3,
              sm: 4,
            },
          }}
        >
          <Box>
            <Chip
              icon={<ShieldRounded />}
              label="Tenant-qualified account governance"
              sx={{
                mb: 2,
                color: '#DDD6FE',
                backgroundColor:
                  'rgba(127,86,217,0.14)',

                '& .MuiChip-icon': {
                  color: '#C4B5FD',
                },
              }}
            />

            <Typography
              component="h2"
              sx={{
                maxWidth: 760,
                fontSize: {
                  xs: '1.85rem',
                  sm: '2.4rem',
                },
                lineHeight: 1.08,
                fontWeight: 750,
                letterSpacing: '-0.045em',
              }}
            >
              Control every tenant account
              without crossing data boundaries.
            </Typography>

            <Typography
              sx={{
                maxWidth: 720,
                mt: 1.5,
                color:
                  'rgba(255,255,255,0.57)',
                fontSize: '0.84rem',
                lineHeight: 1.7,
              }}
            >
              Create accounts, maintain identity,
              assign roles, manage access, verify
              email ownership, reset credentials
              and revoke compromised sessions.
            </Typography>
          </Box>

          <Button
            variant="contained"
            size="large"
            startIcon={<AddRounded />}
            onClick={openCreate}
            sx={{
              minWidth: 195,
              minHeight: 54,
              color: '#FFFFFF',
              background:
                'linear-gradient(135deg, #7F56D9, #6941C6)',
              boxShadow:
                '0 14px 35px rgba(127,86,217,0.3)',
            }}
          >
            Create user
          </Button>
        </CardContent>
      </Card>

      <Box
        sx={{
          display: 'grid',
          gridTemplateColumns: {
            xs: 'repeat(2, minmax(0,1fr))',
            xl: 'repeat(5, minmax(0,1fr))',
          },
          gap: 1.5,
        }}
      >
        {[
          {
            label: 'Directory total',
            value: total,
            icon: <GroupsRounded />,
            accent: '#7F56D9',
          },
          {
            label: 'Visible active',
            value: summary.active,
            icon: <AccountCircleRounded />,
            accent: '#079455',
          },
          {
            label: 'Visible admins',
            value: summary.admins,
            icon: <ShieldRounded />,
            accent: '#246BFD',
          },
          {
            label: 'Email verified',
            value: summary.verified,
            icon: <VerifiedRounded />,
            accent: '#0891B2',
          },
          {
            label: 'Visible suspended',
            value: summary.suspended,
            icon: <LockResetRounded />,
            accent: '#D92D20',
          },
        ].map((metric) => (
          <Paper
            key={metric.label}
            sx={{
              display: 'flex',
              alignItems: 'center',
              gap: 1.25,
              p: 1.75,
              borderRadius: 3.25,
            }}
          >
            <Box
              sx={{
                width: 39,
                height: 39,
                flexShrink: 0,
                display: 'grid',
                placeItems: 'center',
                borderRadius: 2.5,
                color: metric.accent,
                backgroundColor:
                  alpha(
                    metric.accent,
                    0.075
                  ),

                '& svg': {
                  fontSize: 20,
                },
              }}
            >
              {metric.icon}
            </Box>

            <Box>
              <Typography
                sx={{
                  fontSize: '1.23rem',
                  lineHeight: 1,
                  fontWeight: 730,
                }}
              >
                {metric.value.toLocaleString()}
              </Typography>

              <Typography
                sx={{
                  mt: 0.4,
                  color: 'text.secondary',
                  fontSize: '0.61rem',
                }}
              >
                {metric.label}
              </Typography>
            </Box>
          </Paper>
        ))}
      </Box>

      <Card>
        <CardContent>
          <Box
            sx={{
              display: 'flex',
              alignItems: {
                xs: 'stretch',
                lg: 'center',
              },
              justifyContent:
                'space-between',
              flexDirection: {
                xs: 'column',
                lg: 'row',
              },
              gap: 2,
              mb: 2.5,
            }}
          >
            <Box>
              <Typography
                variant="overline"
                sx={{
                  color: '#6941C6',
                }}
              >
                Platform user directory
              </Typography>

              <Typography
                component="h3"
                sx={{
                  mt: 0.45,
                  fontSize: '1.3rem',
                  fontWeight: 710,
                  letterSpacing: '-0.025em',
                }}
              >
                Tenant user accounts
              </Typography>
            </Box>

            <Box
              sx={{
                display: 'flex',
                flexWrap: 'wrap',
                alignItems: 'center',
                gap: 1,
              }}
            >
              <TextField
                size="small"
                placeholder="Search users"
                value={search}
                onChange={(event) => {
                  setPage(0);
                  setSearch(
                    event.target.value
                  );
                }}
                slotProps={{
                  input: {
                    startAdornment: (
                      <InputAdornment position="start">
                        <SearchRounded />
                      </InputAdornment>
                    ),
                  },
                }}
              />

              <TextField
                select
                size="small"
                label="Tenant"
                value={tenantFilter}
                onChange={(event) => {
                  setPage(0);
                  setTenantFilter(
                    event.target.value
                  );
                }}
                sx={{
                  minWidth: 220,
                }}
              >
                <MenuItem value="">
                  All laboratories
                </MenuItem>

                {tenants.map(
                  (tenant) => (
                    <MenuItem
                      key={tenant.id}
                      value={String(
                        tenant.id
                      )}
                    >
                      {tenant.name}
                    </MenuItem>
                  )
                )}
              </TextField>

              <Tooltip title="Refresh users">
                <IconButton
                  disabled={isFetching}
                  onClick={() => {
                    void refetch();
                  }}
                >
                  {isFetching ? (
                    <CircularProgress
                      size={20}
                    />
                  ) : (
                    <RefreshRounded />
                  )}
                </IconButton>
              </Tooltip>
            </Box>
          </Box>

          {users.length === 0 ? (
            <Box
              sx={{
                minHeight: 290,
                display: 'grid',
                placeItems: 'center',
                textAlign: 'center',
              }}
            >
              <Box>
                <GroupsRounded
                  sx={{
                    color: 'text.disabled',
                    fontSize: 48,
                  }}
                />

                <Typography
                  sx={{
                    mt: 1.5,
                    fontWeight: 680,
                  }}
                >
                  No user accounts found
                </Typography>

                <Typography
                  sx={{
                    mt: 0.5,
                    color: 'text.secondary',
                    fontSize: '0.75rem',
                  }}
                >
                  Create an account or revise the
                  current search and tenant filter.
                </Typography>
              </Box>
            </Box>
          ) : (
            <Box
              sx={{
                display: 'grid',
                gap: 1.1,
              }}
            >
              {users.map((user) => (
                <Paper
                  key={user.user_key}
                  variant="outlined"
                  sx={{
                    display: 'grid',
                    gridTemplateColumns: {
                      xs: '1fr',
                      lg: 'minmax(250px,1.3fr) minmax(210px,1fr) 150px 135px 130px auto',
                    },
                    alignItems: 'center',
                    gap: {
                      xs: 1.5,
                      lg: 1,
                    },
                    p: 1.6,
                    borderRadius: 3.25,
                    transition:
                      'transform 180ms ease, border-color 180ms ease, box-shadow 180ms ease',

                    '&:hover': {
                      transform:
                        'translateY(-2px)',
                      borderColor:
                        alpha(
                          '#7F56D9',
                          0.2
                        ),
                      boxShadow:
                        '0 12px 30px rgba(11,18,32,0.06)',
                    },
                  }}
                >
                  <Box
                    sx={{
                      minWidth: 0,
                      display: 'flex',
                      alignItems: 'center',
                      gap: 1.25,
                    }}
                  >
                    <Avatar
                      sx={{
                        width: 44,
                        height: 44,
                        flexShrink: 0,
                        color: '#FFFFFF',
                        fontSize: '0.7rem',
                        fontWeight: 720,
                        background:
                          user.role === 'admin'
                            ? 'linear-gradient(145deg, #7F56D9, #6941C6)'
                            : 'linear-gradient(145deg, #0E7490, #0891B2)',
                      }}
                    >
                      {getInitials(
                        user.name
                      )}
                    </Avatar>

                    <Box sx={{ minWidth: 0 }}>
                      <Typography
                        sx={{
                          overflow: 'hidden',
                          fontSize: '0.8rem',
                          fontWeight: 680,
                          textOverflow:
                            'ellipsis',
                          whiteSpace: 'nowrap',
                        }}
                      >
                        {user.name}
                      </Typography>

                      <Typography
                        sx={{
                          display: 'flex',
                          alignItems: 'center',
                          gap: 0.5,
                          mt: 0.3,
                          overflow: 'hidden',
                          color:
                            'text.secondary',
                          fontSize: '0.64rem',
                          textOverflow:
                            'ellipsis',
                          whiteSpace: 'nowrap',
                        }}
                      >
                        <EmailRounded
                          sx={{
                            fontSize: 13,
                          }}
                        />
                        {user.email}
                      </Typography>
                    </Box>
                  </Box>

                  <Box
                    sx={{
                      minWidth: 0,
                      display: 'flex',
                      alignItems: 'center',
                      gap: 1,
                    }}
                  >
                    <ApartmentRounded
                      sx={{
                        flexShrink: 0,
                        color:
                          'text.disabled',
                        fontSize: 19,
                      }}
                    />

                    <Box sx={{ minWidth: 0 }}>
                      <Typography
                        sx={{
                          overflow: 'hidden',
                          fontSize: '0.72rem',
                          fontWeight: 650,
                          textOverflow:
                            'ellipsis',
                          whiteSpace: 'nowrap',
                        }}
                      >
                        {user.tenant_name}
                      </Typography>

                      <Typography
                        sx={{
                          mt: 0.2,
                          overflow: 'hidden',
                          color:
                            'text.secondary',
                          fontSize: '0.61rem',
                          textOverflow:
                            'ellipsis',
                          whiteSpace: 'nowrap',
                        }}
                      >
                        {user.tenant_domain}
                      </Typography>
                    </Box>
                  </Box>

                  <Chip
                    label={roleLabel(
                      user.role
                    )}
                    size="small"
                    color={
                      user.role === 'admin'
                        ? 'secondary'
                        : user.role ===
                            'doctor'
                          ? 'info'
                          : user.role ===
                              'lab_technician'
                            ? 'success'
                            : 'warning'
                    }
                    sx={{
                      justifySelf: {
                        xs: 'start',
                        lg: 'center',
                      },
                    }}
                  />

                  <Chip
                    label={
                      user.is_active
                        ? 'Active'
                        : 'Suspended'
                    }
                    size="small"
                    color={
                      user.is_active
                        ? 'success'
                        : 'error'
                    }
                    sx={{
                      justifySelf: {
                        xs: 'start',
                        lg: 'center',
                      },
                    }}
                  />

                  <Chip
                    icon={
                      user.email_verified ? (
                        <VerifiedRounded />
                      ) : undefined
                    }
                    label={
                      user.email_verified
                        ? 'Verified'
                        : 'Unverified'
                    }
                    size="small"
                    color={
                      user.email_verified
                        ? 'info'
                        : 'default'
                    }
                    sx={{
                      justifySelf: {
                        xs: 'start',
                        lg: 'center',
                      },
                    }}
                  />

                  <Box
                    sx={{
                      display: 'flex',
                      justifyContent: {
                        xs: 'flex-start',
                        lg: 'flex-end',
                      },
                    }}
                  >
                    <IconButton
                      aria-label={`Manage ${user.name}`}
                      onClick={(event) => {
                        openMenu(
                          event,
                          user
                        );
                      }}
                    >
                      <MoreHorizRounded />
                    </IconButton>
                  </Box>
                </Paper>
              ))}
            </Box>
          )}

          <TablePagination
            component="div"
            count={total}
            page={page}
            rowsPerPage={rowsPerPage}
            rowsPerPageOptions={[
              10,
              25,
              50,
              100,
            ]}
            onPageChange={(
              _event,
              nextPage
            ) => {
              setPage(nextPage);
            }}
            onRowsPerPageChange={(
              event
            ) => {
              setRowsPerPage(
                Number(
                  event.target.value
                )
              );
              setPage(0);
            }}
            sx={{
              mt: 2,
              borderTop: '1px solid',
              borderColor: 'divider',
            }}
          />
        </CardContent>
      </Card>

      <Menu
        anchorEl={menuAnchor}
        open={Boolean(menuAnchor)}
        onClose={closeMenu}
      >
        <MenuItem
          onClick={() => {
            if (menuUser) {
              openEdit(menuUser);
            }
          }}
        >
          <EditRounded
            sx={{
              mr: 1.25,
              fontSize: 20,
            }}
          />
          Edit complete account
        </MenuItem>

        <MenuItem
          onClick={() => {
            if (menuUser) {
              openPasswordReset(
                menuUser
              );
            }
          }}
        >
          <LockResetRounded
            sx={{
              mr: 1.25,
              fontSize: 20,
            }}
          />
          Reset password
        </MenuItem>

        <MenuItem
          sx={{
            color: 'error.main',
          }}
          onClick={() => {
            if (menuUser) {
              openDelete(menuUser);
            }
          }}
        >
          <DeleteOutlineRounded
            sx={{
              mr: 1.25,
              fontSize: 20,
            }}
          />
          Delete account
        </MenuItem>
      </Menu>

      <PlatformUserDialog
        open={editorOpen}
        user={editingUser}
        tenants={tenants}
        roles={roles}
        submitting={
          createMutation.isPending ||
          updateMutation.isPending
        }
        serverError={editorError}
        onClose={() => {
          setEditorOpen(false);
          setEditingUser(null);
          setEditorError(null);
        }}
        onCreate={async (payload) => {
          await createMutation.mutateAsync(
            payload
          );
        }}
        onUpdate={async (
          user,
          payload
        ) => {
          await updateMutation.mutateAsync({
            user,
            payload,
          });
        }}
      />

      <PlatformUserPasswordDialog
        open={Boolean(passwordUser)}
        user={passwordUser}
        submitting={
          passwordMutation.isPending
        }
        serverError={passwordError}
        onClose={() => {
          setPasswordUser(null);
          setPasswordError(null);
        }}
        onSubmit={async (
          user,
          payload
        ) => {
          await passwordMutation.mutateAsync({
            user,
            payload,
          });
        }}
      />

      <Dialog
        open={Boolean(deletingUser)}
        onClose={
          deleteMutation.isPending
            ? undefined
            : () => {
                setDeletingUser(null);
                setDeleteConfirmation('');
              }
        }
        maxWidth="sm"
        fullWidth
      >
        <DialogTitle>
          Delete tenant user account
        </DialogTitle>

        <DialogContent>
          <Alert
            severity="error"
            sx={{
              mb: 2,
            }}
          >
            This permanently removes the account
            from {deletingUser?.tenant_name}. The
            operation cannot be undone.
          </Alert>

          <Typography
            sx={{
              mb: 1.5,
              color: 'text.secondary',
              fontSize: '0.76rem',
              lineHeight: 1.6,
            }}
          >
            Type{' '}
            <Box
              component="strong"
              sx={{
                color: 'text.primary',
              }}
            >
              {deletingUser?.email}
            </Box>{' '}
            to confirm deletion.
          </Typography>

          <TextField
            fullWidth
            autoFocus
            label="User email address"
            value={deleteConfirmation}
            onChange={(event) => {
              setDeleteConfirmation(
                event.target.value
              );
            }}
          />
        </DialogContent>

        <DialogActions
          sx={{
            px: 3,
            pb: 2.5,
          }}
        >
          <Button
            variant="outlined"
            disabled={
              deleteMutation.isPending
            }
            onClick={() => {
              setDeletingUser(null);
              setDeleteConfirmation('');
            }}
          >
            Cancel
          </Button>

          <Button
            variant="contained"
            color="error"
            disabled={
              deleteMutation.isPending ||
              !deleteConfirmed
            }
            startIcon={
              deleteMutation.isPending ? (
                <CircularProgress
                  size={16}
                  color="inherit"
                />
              ) : (
                <DeleteOutlineRounded />
              )
            }
            onClick={() => {
              if (deletingUser) {
                deleteMutation.mutate(
                  deletingUser
                );
              }
            }}
          >
            {deleteMutation.isPending
              ? 'Deleting account'
              : 'Delete permanently'}
          </Button>
        </DialogActions>
      </Dialog>
    </Box>
  );
};

export default AllUsers;