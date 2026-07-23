import AddBusinessRounded from '@mui/icons-material/AddBusinessRounded';
import ApartmentRounded from '@mui/icons-material/ApartmentRounded';
import AssignmentTurnedInRounded from '@mui/icons-material/AssignmentTurnedInRounded';
import BiotechRounded from '@mui/icons-material/BiotechRounded';
import DeleteOutlineRounded from '@mui/icons-material/DeleteOutlineRounded';
import EditRounded from '@mui/icons-material/EditRounded';
import EngineeringRounded from '@mui/icons-material/EngineeringRounded';
import LockResetRounded from '@mui/icons-material/LockResetRounded';
import PeopleAltRounded from '@mui/icons-material/PeopleAltRounded';
import PowerSettingsNewRounded from '@mui/icons-material/PowerSettingsNewRounded';
import RefreshRounded from '@mui/icons-material/RefreshRounded';
import SearchRounded from '@mui/icons-material/SearchRounded';
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
  TextField,
  Tooltip,
  Typography,
  alpha,
} from '@mui/material';

import MoreHorizRounded from '@mui/icons-material/MoreHorizRounded';

import TenantFormDialog from './TenantFormDialog';
import TenantPasswordResetDialog from './TenantPasswordResetDialog';

import {
  safeNumber,
} from './tenantManagementSelectors';

import {
  useTenantManagement,
} from './useTenantManagement';

const AllTenants = () => {
  const {
    search,
    setSearch,
    formOpen,
    setFormOpen,
    editingTenant,
    setEditingTenant,
    passwordTenant,
    setPasswordTenant,
    confirmationTenant,
    setConfirmationTenant,
    confirmationAction,
    setConfirmationAction,
    deleteConfirmation,
    setDeleteConfirmation,
    formError,
    setFormError,
    passwordError,
    setPasswordError,
    menuAnchor,
    menuTenant,
    tenants,
    isPending,
    isError,
    isFetching,
    refetch,
    createMutation,
    updateMutation,
    statusMutation,
    deleteMutation,
    passwordMutation,
    impersonationMutation,
    filteredTenants,
    totals,
    activeTenants,
    closeMenu,
    openMenu,
    openCreateDialog,
    openEditDialog,
    openPasswordDialog,
    openConfirmation,
    confirmationTitle,
    confirmationBusy,
    deleteConfirmed,
  } = useTenantManagement();

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
          height={190}
          sx={{
            borderRadius: 4,
          }}
        />

        {[0, 1, 2].map((item) => (
          <Skeleton
            key={item}
            variant="rounded"
            height={125}
            sx={{
              borderRadius: 4,
            }}
          />
        ))}
      </Box>
    );
  }

  if (isError) {
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
          Rezhel could not load the tenant
          portfolio.
        </Alert>
      </Box>
    );
  }

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
              icon={<VerifiedRounded />}
              label="Landlord-controlled provisioning"
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
                  xs: '1.8rem',
                  sm: '2.35rem',
                },
                lineHeight: 1.08,
                fontWeight: 750,
                letterSpacing: '-0.045em',
              }}
            >
              Govern every laboratory
              tenant from one command surface.
            </Typography>

            <Typography
              sx={{
                maxWidth: 690,
                mt: 1.5,
                color:
                  'rgba(255,255,255,0.57)',
                fontSize: '0.84rem',
                lineHeight: 1.7,
              }}
            >
              Provision laboratories, manage
              availability, secure administrator
              access and enter tenant maintenance
              sessions without weakening tenant
              isolation.
            </Typography>
          </Box>

          <Button
            variant="contained"
            size="large"
            startIcon={
              <AddBusinessRounded />
            }
            onClick={openCreateDialog}
            sx={{
              minWidth: 210,
              minHeight: 54,
              color: '#FFFFFF',
              background:
                'linear-gradient(135deg, #7F56D9, #6941C6)',
              boxShadow:
                '0 14px 35px rgba(127,86,217,0.3)',
            }}
          >
            Create laboratory
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
            label: 'Tenants',
            value: tenants.length,
            icon: <ApartmentRounded />,
            accent: '#7F56D9',
          },
          {
            label: 'Active',
            value: activeTenants,
            icon:
              <PowerSettingsNewRounded />,
            accent: '#079455',
          },
          {
            label: 'Users',
            value: totals.users,
            icon: <PeopleAltRounded />,
            accent: '#246BFD',
          },
          {
            label: 'Tests',
            value: totals.tests,
            icon: <BiotechRounded />,
            accent: '#0891B2',
          },
          {
            label: 'Results',
            value: totals.results,
            icon:
              <AssignmentTurnedInRounded />,
            accent: '#DC6803',
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
                display: 'grid',
                placeItems: 'center',
                flexShrink: 0,
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
                  fontSize: '1.25rem',
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
                  fontSize: '0.62rem',
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
                sm: 'center',
              },
              justifyContent:
                'space-between',
              flexDirection: {
                xs: 'column',
                sm: 'row',
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
                Tenant portfolio
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
                Managed laboratories
              </Typography>
            </Box>

            <Box
              sx={{
                display: 'flex',
                alignItems: 'center',
                gap: 1,
              }}
            >
              <TextField
                size="small"
                placeholder="Search tenants"
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
                        <SearchRounded />
                      </InputAdornment>
                    ),
                  },
                }}
              />

              <Tooltip title="Refresh tenants">
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

          {filteredTenants.length ===
          0 ? (
            <Box
              sx={{
                minHeight: 290,
                display: 'grid',
                placeItems: 'center',
                textAlign: 'center',
              }}
            >
              <Box>
                <ApartmentRounded
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
                  No laboratories found
                </Typography>

                <Typography
                  sx={{
                    mt: 0.5,
                    color: 'text.secondary',
                    fontSize: '0.75rem',
                  }}
                >
                  Create a new laboratory or
                  revise the search term.
                </Typography>
              </Box>
            </Box>
          ) : (
            <Box
              sx={{
                display: 'grid',
                gap: 1.25,
              }}
            >
              {filteredTenants.map(
                (tenant) => (
                  <Paper
                    key={tenant.id}
                    variant="outlined"
                    sx={{
                      display: 'grid',
                      gridTemplateColumns: {
                        xs: '1fr',
                        lg: 'minmax(260px,1.4fr) repeat(4,minmax(70px,0.35fr)) auto',
                      },
                      alignItems: 'center',
                      gap: {
                        xs: 1.5,
                        lg: 1,
                      },
                      p: 1.75,
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
                        gap: 1.35,
                      }}
                    >
                      <Avatar
                        sx={{
                          width: 46,
                          height: 46,
                          flexShrink: 0,
                          color: '#6941C6',
                          background:
                            'linear-gradient(145deg, rgba(127,86,217,0.13), rgba(8,145,178,0.08))',
                        }}
                      >
                        <ApartmentRounded />
                      </Avatar>

                      <Box sx={{ minWidth: 0 }}>
                        <Box
                          sx={{
                            display: 'flex',
                            alignItems: 'center',
                            flexWrap: 'wrap',
                            gap: 0.75,
                          }}
                        >
                          <Typography
                            sx={{
                              overflow:
                                'hidden',
                              fontSize:
                                '0.82rem',
                              fontWeight: 680,
                              textOverflow:
                                'ellipsis',
                              whiteSpace:
                                'nowrap',
                            }}
                          >
                            {tenant.name}
                          </Typography>

                          <Chip
                            label={
                              tenant.is_active
                                ? 'Active'
                                : 'Suspended'
                            }
                            size="small"
                            color={
                              tenant.is_active
                                ? 'success'
                                : 'error'
                            }
                            sx={{
                              height: 23,
                              fontSize:
                                '0.59rem',
                            }}
                          />
                        </Box>

                        <Typography
                          sx={{
                            mt: 0.35,
                            color:
                              'text.secondary',
                            fontSize: '0.67rem',
                          }}
                        >
                          {tenant.domain}
                        </Typography>
                      </Box>
                    </Box>

                    {[
                      {
                        label: 'Users',
                        value:
                          tenant.stats?.users,
                      },
                      {
                        label: 'Patients',
                        value:
                          tenant.stats
                            ?.patients,
                      },
                      {
                        label: 'Tests',
                        value:
                          tenant.stats
                            ?.lab_tests,
                      },
                      {
                        label: 'Results',
                        value:
                          tenant.stats
                            ?.test_results,
                      },
                    ].map((item) => (
                      <Box
                        key={item.label}
                        sx={{
                          textAlign: {
                            xs: 'left',
                            lg: 'center',
                          },
                        }}
                      >
                        <Typography
                          sx={{
                            fontSize: '0.82rem',
                            fontWeight: 690,
                          }}
                        >
                          {safeNumber(
                            item.value
                          ).toLocaleString()}
                        </Typography>

                        <Typography
                          sx={{
                            mt: 0.15,
                            color:
                              'text.secondary',
                            fontSize: '0.58rem',
                          }}
                        >
                          {item.label}
                        </Typography>
                      </Box>
                    ))}

                    <Box
                      sx={{
                        display: 'flex',
                        justifyContent: {
                          xs: 'flex-start',
                          lg: 'flex-end',
                        },
                        gap: 0.75,
                      }}
                    >
                      <Tooltip title="Enter maintenance mode">
                        <span>
                          <Button
                            size="small"
                            variant="outlined"
                            disabled={
                              !tenant.is_active ||
                              impersonationMutation.isPending
                            }
                            startIcon={
                              <EngineeringRounded />
                            }
                            onClick={() => {
                              impersonationMutation.mutate(
                                tenant
                              );
                            }}
                          >
                            Maintain
                          </Button>
                        </span>
                      </Tooltip>

                      <IconButton
                        aria-label={`Manage ${tenant.name}`}
                        onClick={(event) => {
                          openMenu(
                            event,
                            tenant
                          );
                        }}
                      >
                        <MoreHorizRounded />
                      </IconButton>
                    </Box>
                  </Paper>
                )
              )}
            </Box>
          )}
        </CardContent>
      </Card>

      <Menu
        anchorEl={menuAnchor}
        open={Boolean(menuAnchor)}
        onClose={closeMenu}
      >
        <MenuItem
          onClick={() => {
            if (menuTenant) {
              openEditDialog(
                menuTenant
              );
            }
          }}
        >
          <EditRounded
            sx={{
              mr: 1.25,
              fontSize: 20,
            }}
          />
          Edit tenant
        </MenuItem>

        <MenuItem
          onClick={() => {
            if (menuTenant) {
              openPasswordDialog(
                menuTenant
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
          Reset admin password
        </MenuItem>

        <MenuItem
          onClick={() => {
            if (menuTenant) {
              openConfirmation(
                menuTenant,
                menuTenant.is_active
                  ? 'suspend'
                  : 'activate'
              );
            }
          }}
        >
          <PowerSettingsNewRounded
            sx={{
              mr: 1.25,
              fontSize: 20,
            }}
          />
          {menuTenant?.is_active
            ? 'Suspend tenant'
            : 'Activate tenant'}
        </MenuItem>

        <MenuItem
          sx={{
            color: 'error.main',
          }}
          onClick={() => {
            if (menuTenant) {
              openConfirmation(
                menuTenant,
                'delete'
              );
            }
          }}
        >
          <DeleteOutlineRounded
            sx={{
              mr: 1.25,
              fontSize: 20,
            }}
          />
          Delete tenant
        </MenuItem>
      </Menu>

      <TenantFormDialog
        open={formOpen}
        tenant={editingTenant}
        submitting={
          createMutation.isPending ||
          updateMutation.isPending
        }
        serverError={formError}
        onClose={() => {
          setFormOpen(false);
          setEditingTenant(null);
          setFormError(null);
        }}
        onCreate={async (payload) => {
          await createMutation.mutateAsync(
            payload
          );
        }}
        onUpdate={async (
          tenantId,
          payload
        ) => {
          await updateMutation.mutateAsync({
            tenantId,
            payload,
          });
        }}
      />

      <TenantPasswordResetDialog
        open={Boolean(passwordTenant)}
        tenant={passwordTenant}
        submitting={
          passwordMutation.isPending
        }
        serverError={passwordError}
        onClose={() => {
          setPasswordTenant(null);
          setPasswordError(null);
        }}
        onSubmit={async (
          tenantId,
          payload
        ) => {
          await passwordMutation.mutateAsync({
            tenantId,
            payload,
          });
        }}
      />

      <Dialog
        open={Boolean(
          confirmationTenant &&
            confirmationAction
        )}
        onClose={
          confirmationBusy
            ? undefined
            : () => {
                setConfirmationTenant(null);
                setConfirmationAction(null);
                setDeleteConfirmation('');
              }
        }
        maxWidth="sm"
        fullWidth
      >
        <DialogTitle>
          {confirmationTitle}
        </DialogTitle>

        <DialogContent>
          {confirmationAction ===
          'delete' ? (
            <>
              <Alert
                severity="error"
                sx={{
                  mb: 2,
                }}
              >
                This permanently removes the
                tenant record and its database.
                This action cannot be undone.
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
                  {confirmationTenant?.domain}
                </Box>{' '}
                to confirm deletion.
              </Typography>

              <TextField
                fullWidth
                autoFocus
                label="Tenant domain"
                value={
                  deleteConfirmation
                }
                onChange={(event) => {
                  setDeleteConfirmation(
                    event.target.value
                  );
                }}
              />
            </>
          ) : (
            <Alert
              severity={
                confirmationAction ===
                'suspend'
                  ? 'warning'
                  : 'info'
              }
            >
              {confirmationAction ===
              'suspend'
                ? `${confirmationTenant?.name} will be prevented from operating normally until reactivated.`
                : `${confirmationTenant?.name} will be restored to active platform status.`}
            </Alert>
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
            disabled={confirmationBusy}
            onClick={() => {
              setConfirmationTenant(null);
              setConfirmationAction(null);
              setDeleteConfirmation('');
            }}
          >
            Cancel
          </Button>

          <Button
            variant="contained"
            color={
              confirmationAction ===
              'delete'
                ? 'error'
                : confirmationAction ===
                    'suspend'
                  ? 'warning'
                  : 'success'
            }
            disabled={
              confirmationBusy ||
              !deleteConfirmed
            }
            startIcon={
              confirmationBusy ? (
                <CircularProgress
                  size={16}
                  color="inherit"
                />
              ) : confirmationAction ===
                'delete' ? (
                <DeleteOutlineRounded />
              ) : (
                <PowerSettingsNewRounded />
              )
            }
            onClick={() => {
              if (
                !confirmationTenant ||
                !confirmationAction
              ) {
                return;
              }

              if (
                confirmationAction ===
                'delete'
              ) {
                deleteMutation.mutate(
                  confirmationTenant
                );

                return;
              }

              statusMutation.mutate({
                tenant:
                  confirmationTenant,
                action:
                  confirmationAction,
              });
            }}
          >
            {confirmationAction ===
            'delete'
              ? 'Delete permanently'
              : confirmationAction ===
                  'suspend'
                ? 'Suspend tenant'
                : 'Activate tenant'}
          </Button>
        </DialogActions>
      </Dialog>
    </Box>
  );
};

export default AllTenants;
