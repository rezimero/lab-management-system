import {
  useQuery,
  useQueryClient,
} from '@tanstack/react-query';

import {
  fetchInventoryAdjustments,
  fetchInventoryAlerts,
  fetchInventoryCategories,
  fetchInventoryItems,
  fetchInventoryMovements,
  fetchInventoryReceipts,
  fetchInventoryStock,
  fetchInventorySuppliers,
  fetchInventoryUnits,
} from '../api/inventoryApi';

export const inventoryQueryKeys = {
  all: ['inventory'] as const,
  categories: ['inventory', 'categories'] as const,
  units: ['inventory', 'units'] as const,
  suppliers: ['inventory', 'suppliers'] as const,
  items: ['inventory', 'items'] as const,
  stock: (search: string, branchUuid: string) => [
    'inventory',
    'stock',
    search,
    branchUuid,
  ] as const,
  alerts: ['inventory', 'alerts'] as const,
  receipts: ['inventory', 'receipts'] as const,
  adjustments: ['inventory', 'adjustments'] as const,
  movements: ['inventory', 'movements'] as const,
};

export const useInventoryWorkspace = (
  search: string,
  branchUuid: string
) => {
  const queryClient = useQueryClient();

  const categories = useQuery({
    queryKey: inventoryQueryKeys.categories,
    queryFn: fetchInventoryCategories,
  });

  const units = useQuery({
    queryKey: inventoryQueryKeys.units,
    queryFn: fetchInventoryUnits,
  });

  const suppliers = useQuery({
    queryKey: inventoryQueryKeys.suppliers,
    queryFn: fetchInventorySuppliers,
  });

  const items = useQuery({
    queryKey: inventoryQueryKeys.items,
    queryFn: () => fetchInventoryItems(),
  });

  const stock = useQuery({
    queryKey: inventoryQueryKeys.stock(search, branchUuid),
    queryFn: () => fetchInventoryStock(search, branchUuid),
  });

  const alerts = useQuery({
    queryKey: inventoryQueryKeys.alerts,
    queryFn: fetchInventoryAlerts,
  });

  const receipts = useQuery({
    queryKey: inventoryQueryKeys.receipts,
    queryFn: fetchInventoryReceipts,
  });

  const adjustments = useQuery({
    queryKey: inventoryQueryKeys.adjustments,
    queryFn: fetchInventoryAdjustments,
  });

  const movements = useQuery({
    queryKey: inventoryQueryKeys.movements,
    queryFn: fetchInventoryMovements,
  });

  const refresh = async (): Promise<void> => {
    await queryClient.invalidateQueries({
      queryKey: inventoryQueryKeys.all,
    });
  };

  const queries = [
    categories,
    units,
    suppliers,
    items,
    stock,
    alerts,
    receipts,
    adjustments,
    movements,
  ];

  return {
    categories,
    units,
    suppliers,
    items,
    stock,
    alerts,
    receipts,
    adjustments,
    movements,
    loading: queries.some((query) => query.isPending),
    error: queries.find((query) => query.isError)?.error ?? null,
    refresh,
  };
};
