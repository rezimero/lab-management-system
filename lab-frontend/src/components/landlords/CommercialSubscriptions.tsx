import {
  useMemo,
  useState,
} from 'react';

import AddRounded from '@mui/icons-material/AddRounded';
import AutorenewRounded from '@mui/icons-material/AutorenewRounded';
import CreditCardRounded from '@mui/icons-material/CreditCardRounded';
import EditRounded from '@mui/icons-material/EditRounded';
import WorkspacePremiumRounded from '@mui/icons-material/WorkspacePremiumRounded';

import {
  Alert,
  Box,
  Button,
  Card,
  CardContent,
  Checkbox,
  Chip,
  Dialog,
  DialogActions,
  DialogContent,
  DialogTitle,
  FormControlLabel,
  MenuItem,
  Stack,
  Table,
  TableBody,
  TableCell,
  TableContainer,
  TableHead,
  TableRow,
  TextField,
  Typography,
} from '@mui/material';

import {
  useMutation,
  useQuery,
  useQueryClient,
} from '@tanstack/react-query';

import {
  assignSubscription,
  fetchCommercialPlans,
  fetchSubscriptions,
  saveCommercialPlan,
} from '../../features/commercial/api/commercialApi';

import type {
  CommercialFeatureKey,
  CommercialPlan,
  CommercialPlanPayload,
  TenantSubscriptionSummary,
} from '../../features/commercial/types/commercial.types';

import {
  apiErrorMessage,
} from '../../features/test-results/utils/apiErrorMessage';

interface FeatureDefinition {
  key: CommercialFeatureKey;
  name: string;
}

const featureCatalog: FeatureDefinition[] = [
  {
    key: 'multi_branch',
    name: 'Multi-branch and collection centres',
  },
  {
    key: 'billing',
    name: 'Billing, invoices and payments',
  },
  {
    key: 'inventory',
    name: 'Inventory and reagent management',
  },
  {
    key: 'referral_labs',
    name: 'Referral laboratories',
  },
  {
    key: 'patient_portal',
    name: 'Patient portal',
  },
  {
    key: 'clinician_portal',
    name: 'Clinician portal',
  },
  {
    key: 'notifications',
    name: 'Notifications',
  },
  {
    key: 'analyzer_integration',
    name: 'Analyzer integration',
  },
  {
    key: 'interoperability',
    name: 'HL7, ASTM and FHIR interoperability',
  },
  {
    key: 'advanced_analytics',
    name: 'Advanced analytics',
  },
];

const implementedFeatureKeys = new Set<CommercialFeatureKey>([
  'multi_branch',
  'billing',
]);

const createPlanForm = (
  plan?: CommercialPlan
): CommercialPlanPayload => ({
  code: plan?.code ?? '',
  name: plan?.name ?? '',
  description: plan?.description ?? '',
  currency: plan?.currency ?? 'NGN',
  monthly_price: plan?.monthly_price ?? '0.00',
  annual_price: plan?.annual_price ?? '0.00',
  trial_days: plan?.trial_days ?? 30,
  is_default: plan?.is_default ?? false,
  is_public: plan?.is_public ?? true,
  is_active: plan?.is_active ?? true,
  sort_order: 0,
  features: featureCatalog.map((feature) => {
    const existing = plan?.features.find(
      (item) =>
        item.feature_key === feature.key
    );

    return {
      key: feature.key,
      name: feature.name,
      is_enabled:
        existing?.is_enabled ??
        implementedFeatureKeys.has(
          feature.key
        ),
      limit_value:
        existing?.limit_value ??
        null,
    };
  }),
});

const money = (
  amount: string,
  currency: string
): string => {
  const value = Number(amount);

  if (!Number.isFinite(value)) {
    return `${currency} ${amount}`;
  }

  return new Intl.NumberFormat(
    'en-NG',
    {
      style: 'currency',
      currency,
      maximumFractionDigits: 2,
    }
  ).format(value);
};

const CommercialSubscriptions = () => {
  const queryClient = useQueryClient();

  const [
    selectedSubscription,
    setSelectedSubscription,
  ] = useState<TenantSubscriptionSummary | null>(
    null
  );

  const [
    selectedPlan,
    setSelectedPlan,
  ] = useState<CommercialPlan | null>(null);

  const [
    planDialogOpen,
    setPlanDialogOpen,
  ] = useState(false);

  const [
    planForm,
    setPlanForm,
  ] = useState<CommercialPlanPayload>(
    createPlanForm()
  );

  const [
    planUuid,
    setPlanUuid,
  ] = useState('');

  const [
    status,
    setStatus,
  ] = useState('active');

  const [
    billingInterval,
    setBillingInterval,
  ] = useState('monthly');

  const [
    error,
    setError,
  ] = useState<string | null>(null);

  const plansQuery = useQuery({
    queryKey: [
      'landlord-commercial-plans',
    ],
    queryFn: fetchCommercialPlans,
  });

  const subscriptionsQuery = useQuery({
    queryKey: [
      'landlord-commercial-subscriptions',
    ],
    queryFn: fetchSubscriptions,
  });

  const subscriptions =
    subscriptionsQuery.data?.data ??
    [];

  const plans =
    plansQuery.data ??
    [];

  const activeCount = useMemo(
    () => subscriptions.filter(
      (subscription) => [
        'active',
        'trialing',
      ].includes(subscription.status)
    ).length,
    [subscriptions]
  );

  const assignMutation = useMutation({
    mutationFn: () => {
      if (!selectedSubscription) {
        throw new Error(
          'Select a tenant subscription.'
        );
      }

      return assignSubscription(
        selectedSubscription.tenant.id,
        planUuid,
        status,
        billingInterval
      );
    },
    onSuccess: async () => {
      setSelectedSubscription(null);
      setError(null);

      await queryClient.invalidateQueries({
        queryKey: [
          'landlord-commercial-subscriptions',
        ],
      });
    },
    onError: (
      requestError: unknown
    ) => {
      setError(apiErrorMessage(
        requestError,
        'Failed to update the subscription.'
      ));
    },
  });

  const planMutation = useMutation({
    mutationFn: () => saveCommercialPlan(
      planForm,
      selectedPlan?.uuid
    ),
    onSuccess: async () => {
      setPlanDialogOpen(false);
      setSelectedPlan(null);
      setPlanForm(createPlanForm());
      setError(null);

      await Promise.all([
        queryClient.invalidateQueries({
          queryKey: [
            'landlord-commercial-plans',
          ],
        }),
        queryClient.invalidateQueries({
          queryKey: [
            'landlord-commercial-subscriptions',
          ],
        }),
      ]);
    },
    onError: (
      requestError: unknown
    ) => {
      setError(apiErrorMessage(
        requestError,
        'Failed to save the commercial plan.'
      ));
    },
  });

  const openAssignment = (
    subscription:
      TenantSubscriptionSummary
  ) => {
    setSelectedSubscription(
      subscription
    );

    setPlanUuid(
      subscription.plan.uuid
    );

    setStatus(
      subscription.status === 'trialing'
        ? 'active'
        : subscription.status
    );

    setBillingInterval(
      subscription.billing_interval
    );

    setError(null);
  };

  const openPlan = (
    plan?: CommercialPlan
  ) => {
    setSelectedPlan(plan ?? null);
    setPlanForm(
      createPlanForm(plan)
    );
    setPlanDialogOpen(true);
    setError(null);
  };

  const updateFeature = (
    key: CommercialFeatureKey,
    enabled: boolean
  ) => {
    setPlanForm((current) => ({
      ...current,
      features:
        current.features.map(
          (feature) => (
            feature.key === key
              ? {
                  ...feature,
                  is_enabled: enabled,
                }
              : feature
          )
        ),
    }));
  };

  return (
    <Stack spacing={2.5}>
      <Box
        sx={{
          display: 'flex',
          alignItems: {
            xs: 'flex-start',
            md: 'center',
          },
          justifyContent:
            'space-between',
          flexDirection: {
            xs: 'column',
            md: 'row',
          },
          gap: 2,
        }}
      >
        <Box>
          <Typography
            variant="h5"
            sx={{ fontWeight: 750 }}
          >
            Plans and subscriptions
          </Typography>

          <Typography
            color="text.secondary"
            sx={{ mt: 0.5 }}
          >
            Control tenant plans,
            lifecycle status and commercial
            feature entitlements.
          </Typography>
        </Box>

        <Button
          variant="contained"
          startIcon={<AddRounded />}
          onClick={() => {
            openPlan();
          }}
        >
          Add plan
        </Button>
      </Box>

      {(
        plansQuery.isError ||
        subscriptionsQuery.isError
      ) && (
        <Alert severity="error">
          {apiErrorMessage(
            plansQuery.error ??
              subscriptionsQuery.error,
            'Failed to load the subscription portfolio.'
          )}
        </Alert>
      )}

      <Box
        sx={{
          display: 'grid',
          gridTemplateColumns: {
            xs: '1fr',
            md:
              'repeat(3, minmax(0, 1fr))',
          },
          gap: 2,
        }}
      >
        <Card variant="outlined">
          <CardContent>
            <WorkspacePremiumRounded
              color="primary"
            />

            <Typography
              variant="h4"
              sx={{ fontWeight: 750, mt: 1 }}
            >
              {plans.length}
            </Typography>

            <Typography
              color="text.secondary"
            >
              Commercial plans
            </Typography>
          </CardContent>
        </Card>

        <Card variant="outlined">
          <CardContent>
            <AutorenewRounded
              color="success"
            />

            <Typography
              variant="h4"
              sx={{ fontWeight: 750, mt: 1 }}
            >
              {activeCount}
            </Typography>

            <Typography
              color="text.secondary"
            >
              Active or trialing tenants
            </Typography>
          </CardContent>
        </Card>

        <Card variant="outlined">
          <CardContent>
            <CreditCardRounded
              color="secondary"
            />

            <Typography
              variant="h4"
              sx={{ fontWeight: 750, mt: 1 }}
            >
              {subscriptions.length}
            </Typography>

            <Typography
              color="text.secondary"
            >
              Subscription records
            </Typography>
          </CardContent>
        </Card>
      </Box>

      <Box>
        <Typography
          variant="h6"
          sx={{ fontWeight: 700, mb: 1.25 }}
        >
          Plan catalogue
        </Typography>

        <TableContainer
          component={Card}
          variant="outlined"
        >
          <Table>
            <TableHead>
              <TableRow>
                <TableCell>Plan</TableCell>
                <TableCell>Monthly</TableCell>
                <TableCell>Annual</TableCell>
                <TableCell>Trial</TableCell>
                <TableCell>
                  Enabled features
                </TableCell>
                <TableCell>Status</TableCell>
                <TableCell align="right">
                  Action
                </TableCell>
              </TableRow>
            </TableHead>

            <TableBody>
              {plans.map((plan) => (
                <TableRow
                  key={plan.uuid}
                  hover
                >
                  <TableCell>
                    <Typography
                      sx={{ fontWeight: 650 }}
                    >
                      {plan.name}
                    </Typography>

                    <Typography
                      variant="caption"
                      color="text.secondary"
                    >
                      {plan.code}
                    </Typography>
                  </TableCell>

                  <TableCell>
                    {money(
                      plan.monthly_price,
                      plan.currency
                    )}
                  </TableCell>

                  <TableCell>
                    {money(
                      plan.annual_price,
                      plan.currency
                    )}
                  </TableCell>

                  <TableCell>
                    {plan.trial_days} days
                  </TableCell>

                  <TableCell>
                    {plan.features.filter(
                      (feature) =>
                        feature.is_enabled
                    ).length}
                  </TableCell>

                  <TableCell>
                    <Stack
                      direction="row"
                      spacing={0.5}
                    >
                      <Chip
                        size="small"
                        label={
                          plan.is_active
                            ? 'Active'
                            : 'Inactive'
                        }
                      />

                      {plan.is_default && (
                        <Chip
                          size="small"
                          color="primary"
                          label="Default"
                        />
                      )}
                    </Stack>
                  </TableCell>

                  <TableCell align="right">
                    <Button
                      size="small"
                      startIcon={
                        <EditRounded />
                      }
                      onClick={() => {
                        openPlan(plan);
                      }}
                    >
                      Edit
                    </Button>
                  </TableCell>
                </TableRow>
              ))}

              {(
                !plansQuery.isPending &&
                plans.length === 0
              ) && (
                <TableRow>
                  <TableCell
                    colSpan={7}
                    align="center"
                  >
                    No commercial plans
                    are configured.
                  </TableCell>
                </TableRow>
              )}
            </TableBody>
          </Table>
        </TableContainer>
      </Box>

      <Box>
        <Typography
          variant="h6"
          sx={{ fontWeight: 700, mb: 1.25 }}
        >
          Tenant subscriptions
        </Typography>

        <TableContainer
          component={Card}
          variant="outlined"
        >
          <Table>
            <TableHead>
              <TableRow>
                <TableCell>Tenant</TableCell>
                <TableCell>Plan</TableCell>
                <TableCell>Status</TableCell>
                <TableCell>Billing</TableCell>
                <TableCell>
                  Period end
                </TableCell>
                <TableCell align="right">
                  Action
                </TableCell>
              </TableRow>
            </TableHead>

            <TableBody>
              {subscriptions.map(
                (subscription) => (
                  <TableRow
                    key={subscription.uuid}
                    hover
                  >
                    <TableCell>
                      <Typography
                        sx={{ fontWeight: 650 }}
                      >
                        {
                          subscription
                            .tenant.name
                        }
                      </Typography>

                      <Typography
                        variant="caption"
                        color="text.secondary"
                      >
                        {
                          subscription
                            .tenant.domain
                        }
                      </Typography>
                    </TableCell>

                    <TableCell>
                      {subscription.plan.name}
                    </TableCell>

                    <TableCell>
                      <Chip
                        size="small"
                        label={
                          subscription.status
                            .replace('_', ' ')
                        }
                      />
                    </TableCell>

                    <TableCell>
                      {
                        subscription
                          .billing_interval
                      }
                    </TableCell>

                    <TableCell>
                      {
                        subscription
                          .current_period_ends_at
                          ? new Date(
                              subscription
                                .current_period_ends_at
                            )
                              .toLocaleDateString()
                          : 'Open-ended'
                      }
                    </TableCell>

                    <TableCell align="right">
                      <Button
                        size="small"
                        onClick={() => {
                          openAssignment(
                            subscription
                          );
                        }}
                      >
                        Update
                      </Button>
                    </TableCell>
                  </TableRow>
                )
              )}

              {(
                !subscriptionsQuery.isPending &&
                subscriptions.length === 0
              ) && (
                <TableRow>
                  <TableCell
                    colSpan={6}
                    align="center"
                  >
                    No subscription records
                    are available.
                  </TableCell>
                </TableRow>
              )}
            </TableBody>
          </Table>
        </TableContainer>
      </Box>

      <Dialog
        open={planDialogOpen}
        onClose={() => {
          setPlanDialogOpen(false);
        }}
        fullWidth
        maxWidth="md"
      >
        <DialogTitle>
          {selectedPlan
            ? 'Edit commercial plan'
            : 'Create commercial plan'}
        </DialogTitle>

        <DialogContent>
          <Stack
            spacing={2}
            sx={{ mt: 1 }}
          >
            {error && (
              <Alert severity="error">
                {error}
              </Alert>
            )}

            <Stack
              direction={{
                xs: 'column',
                sm: 'row',
              }}
              spacing={2}
            >
              <TextField
                fullWidth
                label="Plan name"
                value={planForm.name}
                onChange={(event) => {
                  setPlanForm(
                    (current) => ({
                      ...current,
                      name:
                        event.target.value,
                    })
                  );
                }}
                required
              />

              <TextField
                fullWidth
                label="Plan code"
                value={planForm.code}
                onChange={(event) => {
                  setPlanForm(
                    (current) => ({
                      ...current,
                      code:
                        event.target.value,
                    })
                  );
                }}
                required
              />
            </Stack>

            <TextField
              label="Description"
              value={
                planForm.description ??
                ''
              }
              onChange={(event) => {
                setPlanForm(
                  (current) => ({
                    ...current,
                    description:
                      event.target.value,
                  })
                );
              }}
              multiline
              minRows={2}
            />

            <Stack
              direction={{
                xs: 'column',
                sm: 'row',
              }}
              spacing={2}
            >
              <TextField
                label="Currency"
                value={planForm.currency}
                onChange={(event) => {
                  setPlanForm(
                    (current) => ({
                      ...current,
                      currency:
                        event.target.value
                          .toUpperCase(),
                    })
                  );
                }}
                slotProps={{ htmlInput: { maxLength: 3, } }}
              />

              <TextField
                fullWidth
                label="Monthly price"
                type="number"
                value={
                  planForm.monthly_price
                }
                onChange={(event) => {
                  setPlanForm(
                    (current) => ({
                      ...current,
                      monthly_price:
                        event.target.value,
                    })
                  );
                }}
              />

              <TextField
                fullWidth
                label="Annual price"
                type="number"
                value={
                  planForm.annual_price
                }
                onChange={(event) => {
                  setPlanForm(
                    (current) => ({
                      ...current,
                      annual_price:
                        event.target.value,
                    })
                  );
                }}
              />

              <TextField
                fullWidth
                label="Trial days"
                type="number"
                value={planForm.trial_days}
                onChange={(event) => {
                  setPlanForm(
                    (current) => ({
                      ...current,
                      trial_days: Number(
                        event.target.value
                      ),
                    })
                  );
                }}
              />
            </Stack>

            <Stack
              direction={{
                xs: 'column',
                sm: 'row',
              }}
              spacing={1}
            >
              <FormControlLabel
                control={
                  <Checkbox
                    checked={
                      planForm.is_default
                    }
                    onChange={(event) => {
                      setPlanForm(
                        (current) => ({
                          ...current,
                          is_default:
                            event.target
                              .checked,
                        })
                      );
                    }}
                  />
                }
                label="Default plan"
              />

              <FormControlLabel
                control={
                  <Checkbox
                    checked={
                      planForm.is_public
                    }
                    onChange={(event) => {
                      setPlanForm(
                        (current) => ({
                          ...current,
                          is_public:
                            event.target
                              .checked,
                        })
                      );
                    }}
                  />
                }
                label="Public plan"
              />

              <FormControlLabel
                control={
                  <Checkbox
                    checked={
                      planForm.is_active
                    }
                    onChange={(event) => {
                      setPlanForm(
                        (current) => ({
                          ...current,
                          is_active:
                            event.target
                              .checked,
                        })
                      );
                    }}
                  />
                }
                label="Active"
              />
            </Stack>

            <Box>
              <Typography
                sx={{ fontWeight: 700, mb: 1 }}
              >
                Feature entitlements
              </Typography>

              <Box
                sx={{
                  display: 'grid',
                  gridTemplateColumns: {
                    xs: '1fr',
                    md:
                      'repeat(2, minmax(0, 1fr))',
                  },
                  gap: 0.5,
                }}
              >
                {planForm.features.map(
                  (feature) => (
                    <FormControlLabel
                      key={feature.key}
                      control={
                        <Checkbox
                          checked={
                            feature
                              .is_enabled
                          }
                          disabled={
                            !implementedFeatureKeys.has(
                              feature.key
                            )
                          }
                          onChange={(
                            event
                          ) => {
                            updateFeature(
                              feature.key,
                              event.target
                                .checked
                            );
                          }}
                        />
                      }
                      label={
                        implementedFeatureKeys.has(
                          feature.key
                        )
                          ? feature.name
                          : `${feature.name} (pending implementation)`
                      }
                    />
                  )
                )}
              </Box>
            </Box>
          </Stack>
        </DialogContent>

        <DialogActions>
          <Button
            onClick={() => {
              setPlanDialogOpen(false);
            }}
          >
            Cancel
          </Button>

          <Button
            variant="contained"
            disabled={
              !planForm.name.trim() ||
              !planForm.code.trim() ||
              planMutation.isPending
            }
            onClick={() => {
              planMutation.mutate();
            }}
          >
            {planMutation.isPending
              ? 'Saving…'
              : 'Save plan'}
          </Button>
        </DialogActions>
      </Dialog>

      <Dialog
        open={Boolean(
          selectedSubscription
        )}
        onClose={() => {
          setSelectedSubscription(null);
        }}
        fullWidth
        maxWidth="sm"
      >
        <DialogTitle>
          Update tenant subscription
        </DialogTitle>

        <DialogContent>
          <Stack
            spacing={2}
            sx={{ mt: 1 }}
          >
            {error && (
              <Alert severity="error">
                {error}
              </Alert>
            )}

            <Typography
              color="text.secondary"
            >
              {
                selectedSubscription
                  ?.tenant.name
              }
              {' · '}
              {
                selectedSubscription
                  ?.tenant.domain
              }
            </Typography>

            <TextField
              select
              label="Plan"
              value={planUuid}
              onChange={(event) => {
                setPlanUuid(
                  event.target.value
                );
              }}
            >
              {plans.filter(
                (plan) => plan.is_active
              ).map((plan) => (
                <MenuItem
                  key={plan.uuid}
                  value={plan.uuid}
                >
                  {plan.name}
                </MenuItem>
              ))}
            </TextField>

            <TextField
              select
              label="Status"
              value={status}
              onChange={(event) => {
                setStatus(
                  event.target.value
                );
              }}
            >
              <MenuItem value="trialing">
                Trialing
              </MenuItem>
              <MenuItem value="active">
                Active
              </MenuItem>
              <MenuItem value="past_due">
                Past due
              </MenuItem>
              <MenuItem value="suspended">
                Suspended
              </MenuItem>
              <MenuItem value="cancelled">
                Cancelled
              </MenuItem>
              <MenuItem value="expired">
                Expired
              </MenuItem>
            </TextField>

            <TextField
              select
              label="Billing interval"
              value={billingInterval}
              onChange={(event) => {
                setBillingInterval(
                  event.target.value
                );
              }}
            >
              <MenuItem value="monthly">
                Monthly
              </MenuItem>
              <MenuItem value="annual">
                Annual
              </MenuItem>
            </TextField>
          </Stack>
        </DialogContent>

        <DialogActions>
          <Button
            onClick={() => {
              setSelectedSubscription(null);
            }}
          >
            Cancel
          </Button>

          <Button
            variant="contained"
            disabled={
              !planUuid ||
              assignMutation.isPending
            }
            onClick={() => {
              assignMutation.mutate();
            }}
          >
            {assignMutation.isPending
              ? 'Updating…'
              : 'Update subscription'}
          </Button>
        </DialogActions>
      </Dialog>
    </Stack>
  );
};

export default CommercialSubscriptions;
