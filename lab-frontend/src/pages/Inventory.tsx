import {
  useState,
} from 'react';

import RefreshRounded from '@mui/icons-material/RefreshRounded';

import {
  Alert,
  Box,
  Button,
  CircularProgress,
  MenuItem,
  Stack,
  Tab,
  Tabs,
  TextField,
  Typography,
} from '@mui/material';

import {
  useQuery,
} from '@tanstack/react-query';

import Layout from '../components/Layout/Layout';
import {
  useAuth,
} from '../contexts/AuthContext';

import {
  fetchBranches,
} from '../features/commercial/api/commercialApi';

import InventoryAlertsPanel from '../features/inventory/components/InventoryAlertsPanel';
import InventoryCatalogPanel from '../features/inventory/components/InventoryCatalogPanel';
import InventoryOperationsPanel from '../features/inventory/components/InventoryOperationsPanel';
import LabTestInventoryRequirementsPanel from '../features/inventory/components/LabTestInventoryRequirementsPanel';
import InventoryStockTable from '../features/inventory/components/InventoryStockTable';
import InventorySummaryCards from '../features/inventory/components/InventorySummaryCards';

import {
  useInventoryWorkspace,
} from '../features/inventory/hooks/useInventoryWorkspace';

import {
  roleAllowsInventoryAction,
} from '../features/inventory/permissions/inventoryPermissions';

import {
  apiErrorMessage,
} from '../features/test-results/utils/apiErrorMessage';

type WorkspaceTab =
  | 'stock'
  | 'alerts'
  | 'catalogue'
  | 'operations'
  | 'test-rules';

const Inventory = () => {
  const auth = useAuth();
  const [tab, setTab] = useState<WorkspaceTab>('stock');
  const [search, setSearch] = useState('');
  const [branchUuid, setBranchUuid] = useState('');

  const workspace = useInventoryWorkspace(search, branchUuid);

  const branches = useQuery({
    queryKey: ['commercial-branches', 'inventory'],
    queryFn: fetchBranches,
  });

  const canManage = roleAllowsInventoryAction(
    auth.user?.role,
    'manage_catalogue'
  );
  const canReceive = roleAllowsInventoryAction(
    auth.user?.role,
    'receive_stock'
  );
  const canAdjust = roleAllowsInventoryAction(
    auth.user?.role,
    'adjust_stock'
  );

  const error = workspace.error ?? branches.error;

  return (
    <Layout title="Inventory and reagents">
      <Stack spacing={3}>
        <Stack
          direction={{ xs: 'column', md: 'row' }}
         sx={{ justifyContent: "space-between", alignItems: { xs: 'stretch', md: 'center' }, gap: 2 }}>
          <div>
            <Typography variant="h4" sx={{ fontWeight: 780 }}>
              Inventory and reagents
            </Typography>
            <Typography color="text.secondary" sx={{ mt: 0.5 }}>
              Control branch stock, lot traceability, expiry exposure and test-linked consumption.
            </Typography>
          </div>
          <Button
            variant="outlined"
            startIcon={<RefreshRounded />}
            onClick={() => {
              void workspace.refresh();
            }}
          >
            Refresh
          </Button>
        </Stack>

        {error && (
          <Alert severity="error">
            {apiErrorMessage(error, 'Inventory data could not be loaded.')}
          </Alert>
        )}

        <InventorySummaryCards
          stockedLots={workspace.stock.data?.meta?.total
            ?? workspace.stock.data?.data.length
            ?? 0}
          alerts={workspace.alerts.data}
        />

        <Box sx={{ borderBottom: 1, borderColor: 'divider' }}>
          <Tabs
            value={tab}
            onChange={(_, value: WorkspaceTab) => setTab(value)}
            variant="scrollable"
            allowScrollButtonsMobile
          >
            <Tab value="stock" label="Stock on hand" />
            <Tab value="alerts" label="Warnings" />
            <Tab value="catalogue" label="Catalogue and suppliers" />
            <Tab value="operations" label="Receipts and ledger" />
            <Tab value="test-rules" label="Test usage rules" />
          </Tabs>
        </Box>

        {workspace.loading && !workspace.stock.data ? (
          <Stack sx={{ py: 8, alignItems: "center" }}>
            <CircularProgress />
          </Stack>
        ) : (
          <>
            {tab === 'stock' && (
              <Stack spacing={2}>
                <Stack direction={{ xs: 'column', sm: 'row' }} sx={{ gap: 2 }}>
                  <TextField
                    label="Search stock"
                    value={search}
                    onChange={(event) => setSearch(event.target.value)}
                    sx={{ flex: 1 }}
                  />
                  <TextField
                    select
                    label="Branch"
                    value={branchUuid}
                    onChange={(event) => setBranchUuid(event.target.value)}
                    sx={{ minWidth: 260 }}
                  >
                    <MenuItem value="">All accessible branches</MenuItem>
                    {(branches.data?.data ?? []).map((branch) => (
                      <MenuItem key={branch.uuid} value={branch.uuid}>
                        {branch.code} · {branch.name}
                      </MenuItem>
                    ))}
                  </TextField>
                </Stack>
                <InventoryStockTable rows={workspace.stock.data?.data ?? []} />
              </Stack>
            )}

            {tab === 'alerts' && (
              <InventoryAlertsPanel alerts={workspace.alerts.data} />
            )}

            {tab === 'catalogue' && (
              <InventoryCatalogPanel
                categories={workspace.categories.data?.data ?? []}
                units={workspace.units.data?.data ?? []}
                suppliers={workspace.suppliers.data?.data ?? []}
                items={workspace.items.data?.data ?? []}
                branches={branches.data?.data ?? []}
                canManage={canManage}
                onChanged={workspace.refresh}
              />
            )}

            {tab === 'operations' && (
              <InventoryOperationsPanel
                branches={branches.data?.data ?? []}
                suppliers={workspace.suppliers.data?.data ?? []}
                items={workspace.items.data?.data ?? []}
                receipts={workspace.receipts.data?.data ?? []}
                adjustments={workspace.adjustments.data?.data ?? []}
                movements={workspace.movements.data?.data ?? []}
                canReceive={canReceive}
                canAdjust={canAdjust}
                onChanged={workspace.refresh}
              />
            )}

            {tab === 'test-rules' && (
              <LabTestInventoryRequirementsPanel
                items={workspace.items.data?.data ?? []}
                canManage={canManage}
              />
            )}
          </>
        )}
      </Stack>
    </Layout>
  );
};

export default Inventory;
