import type {
  ReactNode,
} from 'react';

import {
  Alert,
  Stack,
  Typography,
} from '@mui/material';

import Layout from '../../../components/Layout/Layout';
import AppLoadingScreen from '../../../components/feedback/AppLoadingScreen';

import {
  useAuth,
} from '../../../contexts/AuthContext';

import {
  useTenantEntitlements,
} from '../../commercial/hooks/useTenantEntitlements';

import {
  roleAllowsAnalyzerAction,
} from '../permissions/analyzerPermissions';

interface Props {
  children: ReactNode;
  mode: 'instruments' | 'imports';
}

const AnalyzerAccessRoute = ({
  children,
  mode,
}: Props) => {
  const auth = useAuth();
  const entitlements = useTenantEntitlements();

  if (entitlements.isPending) {
    return <AppLoadingScreen />;
  }

  if (entitlements.isError) {
    return (
      <Layout title="Analyzer integration">
        <Alert severity="error">
          Analyzer entitlement could not be verified. Refresh the page or contact an administrator.
        </Alert>
      </Layout>
    );
  }

  const featureEnabled = Boolean(
    entitlements.data?.features.analyzer_integration?.enabled
  );

  const action =
    mode === 'instruments'
      ? 'view_instruments'
      : 'view_imports';

  const roleAllowed = roleAllowsAnalyzerAction(
    auth.user?.role,
    action
  );

  if (!featureEnabled || !roleAllowed) {
    return (
      <Layout title="Analyzer integration">
        <Stack spacing={2}>
          <Typography variant="h4" sx={{ fontWeight: 760 }}>
            Analyzer integration unavailable
          </Typography>

          <Alert severity="warning">
            {!roleAllowed
              ? 'Your role does not have access to analyzer integration.'
              : 'Analyzer integration is not enabled for the current subscription.'}
          </Alert>
        </Stack>
      </Layout>
    );
  }

  return children;
};

export default AnalyzerAccessRoute;
