import { Alert, Typography } from '@mui/material';
import {
  useNavigate,
  useParams,
} from 'react-router-dom';

import Layout from '../components/Layout/Layout';
import { useAuth } from '../contexts/AuthContext';
import OrderDetailHeader from '../features/laboratory-orders/components/OrderDetailHeader';
import OrderItemsTable from '../features/laboratory-orders/components/OrderItemsTable';
import OrderLifecycleActions from '../features/laboratory-orders/components/OrderLifecycleActions';
import OrderReportActions from '../features/laboratory-orders/components/OrderReportActions';
import OrderSummary from '../features/laboratory-orders/components/OrderSummary';
import OrderViewState from '../features/laboratory-orders/components/OrderViewState';
import SpecimenWorkspace from '../features/laboratory-orders/components/SpecimenWorkspace';
import { useLaboratoryOrder } from '../features/laboratory-orders/hooks/useLaboratoryOrder';
import { useOrderItemStart } from '../features/laboratory-orders/hooks/useOrderItemStart';
import InventoryConsumptionPanel from '../features/inventory/components/InventoryConsumptionPanel';
import { useTenantEntitlements } from '../features/commercial/hooks/useTenantEntitlements';
import { roleAllowsInventoryAction } from '../features/inventory/permissions/inventoryPermissions';
import { useOrderReportActions } from '../features/laboratory-orders/hooks/useOrderReportActions';
import { roleAllowsOrderAction } from '../features/laboratory-orders/permissions/orderPermissions';

const LaboratoryOrderView = () => {
  const navigate = useNavigate();
  const { uuid } = useParams<{ uuid: string }>();
  const { user } = useAuth();
  const resource = useLaboratoryOrder(uuid);
  const itemStart = useOrderItemStart(resource.refresh);
  const entitlements = useTenantEntitlements();

  const report = useOrderReportActions(
    uuid,
    resource.order?.status === 'completed',
    (issued) => {
      navigate(`/laboratory-reports/${issued.uuid}`);
    }
  );

  const canCreateResult = roleAllowsOrderAction(
    user?.role,
    'create_result'
  );

  const canGenerate = roleAllowsOrderAction(
    user?.role,
    'generate_report'
  );

  const canManageSpecimens = roleAllowsOrderAction(
    user?.role,
    'manage_specimens'
  );

  const canManageStatus = roleAllowsOrderAction(
    user?.role,
    'manage_order_status'
  );

  const canViewInventory = Boolean(
    entitlements.data?.features.inventory?.enabled
  ) && roleAllowsInventoryAction(
    user?.role,
    'view'
  );

  const canRecordConsumption = roleAllowsInventoryAction(
    user?.role,
    'record_consumption'
  );

  if (resource.loading || !resource.order) {
    return (
      <Layout title="Laboratory order">
        <OrderViewState
          loading={resource.loading}
          error={resource.error}
        />
      </Layout>
    );
  }

  const order = resource.order;

  return (
    <Layout title="Laboratory order">
      <OrderDetailHeader
        order={order}
        onBack={() => {
          navigate('/laboratory-orders');
        }}
      />

      {itemStart.error && (
        <Alert
          severity="error"
          onClose={itemStart.clearError}
          sx={{ mb: 2 }}
        >
          {itemStart.error}
        </Alert>
      )}

      {report.error && (
        <Alert
          severity="error"
          onClose={report.clearError}
          sx={{ mb: 2 }}
        >
          {report.error}
        </Alert>
      )}

      <OrderSummary order={order} />

      <OrderLifecycleActions
        order={order}
        canManage={canManageStatus}
        onChanged={resource.refresh}
      />

      <OrderReportActions
        order={order}
        canGenerate={canGenerate}
        availability={report.availability}
        action={report.action}
        onView={() => {
          void report.view();
        }}
        onGenerate={() => {
          void report.generate();
        }}
      />

      <SpecimenWorkspace
        order={order}
        canManage={canManageSpecimens}
        onChanged={resource.refresh}
      />

      <Typography
        variant="h6"
        sx={{
          mb: 1.25,
          fontWeight: 720,
        }}
      >
        Ordered tests
      </Typography>

      <OrderItemsTable
        orderUuid={order.uuid}
        orderStatus={order.status}
        items={order.items ?? []}
        canCreateResult={canCreateResult}
        startingUuid={itemStart.startingUuid}
        onStart={(itemUuid) => {
          void itemStart.start(itemUuid);
        }}
        onOpenResult={(resultUuid) => {
          navigate(`/test-results/${resultUuid}`);
        }}
        onEnterResult={(orderUuid, itemUuid) => {
          navigate(
            `/test-results/new?order=${orderUuid}` +
              `&item=${itemUuid}`
          );
        }}
      />


      {canViewInventory && (
        <InventoryConsumptionPanel
          items={order.items ?? []}
          canRecord={canRecordConsumption}
        />
      )}
    </Layout>
  );
};

export default LaboratoryOrderView;