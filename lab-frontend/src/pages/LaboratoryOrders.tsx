import { useNavigate } from 'react-router-dom';
import Layout from '../components/Layout/Layout';
import { useAuth } from '../contexts/AuthContext';
import OrderFilters from '../features/laboratory-orders/components/OrderFilters';
import OrderListHeader from '../features/laboratory-orders/components/OrderListHeader';
import OrderListTable from '../features/laboratory-orders/components/OrderListTable';
import OrderPagination from '../features/laboratory-orders/components/OrderPagination';
import { useLaboratoryOrders } from '../features/laboratory-orders/hooks/useLaboratoryOrders';
import { roleAllowsOrderAction } from '../features/laboratory-orders/permissions/orderPermissions';

const LaboratoryOrders = () => {
  const navigate = useNavigate();
  const { user } = useAuth();
  const orders = useLaboratoryOrders();

  return (
    <Layout title="Laboratory orders">
      <OrderListHeader
        canCreate={roleAllowsOrderAction(user?.role, 'create_order')}
        onCreate={() => navigate('/laboratory-orders/new')}
      />
      <OrderFilters
        query={orders.query}
        status={orders.status}
        priority={orders.priority}
        loading={orders.loading}
        onQueryChange={orders.setQuery}
        onStatusChange={orders.setStatus}
        onPriorityChange={orders.setPriority}
        onApply={orders.applyFilters}
        onClear={orders.clearFilters}
      />
      <OrderListTable
        orders={orders.orders}
        loading={orders.loading}
        error={orders.error}
        onOpen={(uuid) => navigate(`/laboratory-orders/${uuid}`)}
      />
      <OrderPagination
        meta={orders.meta}
        page={orders.page}
        onPageChange={orders.setPage}
      />
    </Layout>
  );
};

export default LaboratoryOrders;
