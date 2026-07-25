import { Alert, Typography } from '@mui/material';
import {
  useNavigate,
  useParams,
} from 'react-router-dom';
import Layout from '../components/Layout/Layout';
import { useAuth } from '../contexts/AuthContext';
import OrderDetailHeader from '../features/laboratory-orders/components/OrderDetailHeader';
import OrderItemsTable from '../features/laboratory-orders/components/OrderItemsTable';
import OrderReportActions from '../features/laboratory-orders/components/OrderReportActions';
import OrderSpecimens from '../features/laboratory-orders/components/OrderSpecimens';
import OrderSummary from '../features/laboratory-orders/components/OrderSummary';
import OrderViewState from '../features/laboratory-orders/components/OrderViewState';
import { useLaboratoryOrder } from '../features/laboratory-orders/hooks/useLaboratoryOrder';
import { useOrderItemStart } from '../features/laboratory-orders/hooks/useOrderItemStart';
import { useOrderReportActions } from '../features/laboratory-orders/hooks/useOrderReportActions';
import { roleAllowsOrderAction } from '../features/laboratory-orders/permissions/orderPermissions';

const LaboratoryOrderView = () => {
  const navigate = useNavigate();
  const { uuid } = useParams<{ uuid: string }>();
  const { user } = useAuth();
  const resource = useLaboratoryOrder(uuid);
  const itemStart = useOrderItemStart(resource.refresh);
  const report = useOrderReportActions((issued) => {
    navigate(`/laboratory-reports/${issued.uuid}`);
  });
  const canCreateResult = roleAllowsOrderAction(user?.role, 'create_result');
  const canGenerate = roleAllowsOrderAction(user?.role, 'generate_report');

  if (resource.loading || !resource.order) {
    return (
      <Layout title="Laboratory order">
        <OrderViewState loading={resource.loading} error={resource.error} />
      </Layout>
    );
  }

  const order = resource.order;
  return (
    <Layout title="Laboratory order">
      <OrderDetailHeader order={order} onBack={() => navigate('/laboratory-orders')} />
      {itemStart.error && (
        <Alert severity="error" onClose={itemStart.clearError} sx={{ mb: 2 }}>
          {itemStart.error}
        </Alert>
      )}
      {report.error && (
        <Alert severity="error" onClose={report.clearError} sx={{ mb: 2 }}>
          {report.error}
        </Alert>
      )}
      <OrderSummary order={order} />
      <OrderReportActions
        order={order}
        canGenerate={canGenerate}
        action={report.action}
        onView={() => void report.view(order.uuid)}
        onGenerate={() => void report.generate(order.uuid)}
      />
      <OrderSpecimens specimens={order.specimens ?? []} />
      <Typography variant="h6" sx={{ mb: 1.25, fontWeight: 720 }}>
        Ordered tests
      </Typography>
      <OrderItemsTable
        orderUuid={order.uuid}
        orderStatus={order.status}
        items={order.items ?? []}
        canCreateResult={canCreateResult}
        startingUuid={itemStart.startingUuid}
        onStart={(itemUuid) => void itemStart.start(itemUuid)}
        onOpenResult={(resultUuid) => navigate(`/test-results/${resultUuid}`)}
        onEnterResult={(orderUuid, itemUuid) => navigate(
          `/test-results/new?order=${orderUuid}&item=${itemUuid}`
        )}
      />
    </Layout>
  );
};

export default LaboratoryOrderView;
