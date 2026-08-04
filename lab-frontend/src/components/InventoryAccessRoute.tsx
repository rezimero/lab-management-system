import type {
  ReactNode,
} from 'react';

import {
  Alert,
  Stack,
  Typography,
} from '@mui/material';

import Layout from './Layout/Layout';
import AppLoadingScreen from './feedback/AppLoadingScreen';

import {
  useAuth,
} from '../contexts/AuthContext';

import {
  useTenantEntitlements,
} from '../features/commercial/hooks/useTenantEntitlements';

import {
  roleAllowsInventoryAction,
} from '../features/inventory/permissions/inventoryPermissions';

interface Props {
  children: ReactNode;
}

const InventoryAccessRoute = ({
  children,
}: Props) => {
  const auth = useAuth();
  const entitlements = useTenantEntitlements();

  if (entitlements.isPending) {
    return <AppLoadingScreen />;
  }

  if (entitlements.isError) {
    return (
      <Layout title="Inventory">
        <Alert severity="error">
          Inventory access could not be verified. Refresh the page or contact an administrator.
        </Alert>
      </Layout>
    );
  }

  const roleAllowed = roleAllowsInventoryAction(
    auth.user?.role,
    'view'
  );

  const featureEnabled = Boolean(
    entitlements.data?.features.inventory?.enabled
  );

  if (!roleAllowed || !featureEnabled) {
    return (
      <Layout title="Inventory">
        <Stack spacing={2}>
          <Typography
            variant="h4"
           sx={{ fontWeight: 760 }}>
            Inventory unavailable
          </Typography>

          <Alert severity="warning">
            {!roleAllowed
              ? 'Your role does not have access to inventory operations.'
              : 'Inventory is not enabled for the current subscription.'}
          </Alert>
        </Stack>
      </Layout>
    );
  }
  return children;
};

export default InventoryAccessRoute;
