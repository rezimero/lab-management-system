import api from '../../../api/client';

import type {
  LabTest,
} from '../../../types';

import type {
  InventoryAdjustment,
  InventoryAdjustmentPayload,
  InventoryAlerts,
  InventoryCategory,
  InventoryConsumption,
  InventoryConsumptionContext,
  InventoryConsumptionPayload,
  InventoryItem,
  InventoryItemPayload,
  LabTestInventoryRequirementContext,
  InventoryMovement,
  InventoryPage,
  InventoryReceipt,
  InventoryReceiptPayload,
  InventoryStockBalance,
  InventorySupplier,
  InventoryUnit,
} from '../types/inventory.types';

interface DataEnvelope<T> {
  data: T;
  message?: string;
}

interface LookupPayload {
  code: string;
  name: string;
  description?: string;
  is_active: boolean;
}

interface UnitPayload {
  code: string;
  name: string;
  symbol: string;
  decimal_places: number;
  is_active: boolean;
}

interface SupplierPayload {
  code: string;
  name: string;
  contact_name?: string;
  email?: string;
  phone?: string;
  address?: string;
  tax_identifier?: string;
  is_active: boolean;
}

const lookupParams = {
  page: 1,
  per_page: 100,
};

export const fetchInventoryCategories = async (): Promise<InventoryPage<InventoryCategory>> => {
  const response = await api.get<InventoryPage<InventoryCategory>>(
    '/inventory/categories',
    { params: lookupParams }
  );

  return response.data;
};

export const saveInventoryCategory = async (
  payload: LookupPayload,
  uuid?: string
): Promise<InventoryCategory> => {
  const response = uuid
    ? await api.put<DataEnvelope<InventoryCategory>>(
        `/inventory/categories/${uuid}`,
        payload
      )
    : await api.post<DataEnvelope<InventoryCategory>>(
        '/inventory/categories',
        payload
      );

  return response.data.data;
};

export const fetchInventoryUnits = async (): Promise<InventoryPage<InventoryUnit>> => {
  const response = await api.get<InventoryPage<InventoryUnit>>(
    '/inventory/units',
    { params: lookupParams }
  );

  return response.data;
};

export const saveInventoryUnit = async (
  payload: UnitPayload,
  uuid?: string
): Promise<InventoryUnit> => {
  const response = uuid
    ? await api.put<DataEnvelope<InventoryUnit>>(
        `/inventory/units/${uuid}`,
        payload
      )
    : await api.post<DataEnvelope<InventoryUnit>>(
        '/inventory/units',
        payload
      );

  return response.data.data;
};

export const fetchInventorySuppliers = async (): Promise<InventoryPage<InventorySupplier>> => {
  const response = await api.get<InventoryPage<InventorySupplier>>(
    '/inventory/suppliers',
    { params: lookupParams }
  );

  return response.data;
};

export const saveInventorySupplier = async (
  payload: SupplierPayload,
  uuid?: string
): Promise<InventorySupplier> => {
  const response = uuid
    ? await api.put<DataEnvelope<InventorySupplier>>(
        `/inventory/suppliers/${uuid}`,
        payload
      )
    : await api.post<DataEnvelope<InventorySupplier>>(
        '/inventory/suppliers',
        payload
      );

  return response.data.data;
};

export const fetchInventoryItems = async (
  search = ''
): Promise<InventoryPage<InventoryItem>> => {
  const response = await api.get<InventoryPage<InventoryItem>>(
    '/inventory/items',
    {
      params: {
        ...lookupParams,
        search: search.trim() || undefined,
      },
    }
  );

  return response.data;
};

export const saveInventoryItem = async (
  payload: InventoryItemPayload,
  uuid?: string
): Promise<InventoryItem> => {
  const response = uuid
    ? await api.put<DataEnvelope<InventoryItem>>(
        `/inventory/items/${uuid}`,
        payload
      )
    : await api.post<DataEnvelope<InventoryItem>>(
        '/inventory/items',
        payload
      );

  return response.data.data;
};

export const fetchInventoryStock = async (
  search = '',
  branchUuid = ''
): Promise<InventoryPage<InventoryStockBalance>> => {
  const response = await api.get<InventoryPage<InventoryStockBalance>>(
    '/inventory/stock',
    {
      params: {
        ...lookupParams,
        search: search.trim() || undefined,
        branch_uuid: branchUuid || undefined,
      },
    }
  );

  return response.data;
};

export const fetchInventoryAlerts = async (): Promise<InventoryAlerts> => {
  const response = await api.get<DataEnvelope<InventoryAlerts>>(
    '/inventory/alerts',
    { params: { expiry_days: 30 } }
  );

  return response.data.data;
};

export const fetchInventoryMovements = async (): Promise<InventoryPage<InventoryMovement>> => {
  const response = await api.get<InventoryPage<InventoryMovement>>(
    '/inventory/movements',
    { params: lookupParams }
  );

  return response.data;
};

export const fetchInventoryReceipts = async (): Promise<InventoryPage<InventoryReceipt>> => {
  const response = await api.get<InventoryPage<InventoryReceipt>>(
    '/inventory/receipts',
    { params: lookupParams }
  );

  return response.data;
};

export const createInventoryReceipt = async (
  payload: InventoryReceiptPayload
): Promise<InventoryReceipt> => {
  const response = await api.post<DataEnvelope<InventoryReceipt>>(
    '/inventory/receipts',
    payload
  );

  return response.data.data;
};

export const fetchInventoryAdjustments = async (): Promise<InventoryPage<InventoryAdjustment>> => {
  const response = await api.get<InventoryPage<InventoryAdjustment>>(
    '/inventory/adjustments',
    { params: lookupParams }
  );

  return response.data;
};

export const createInventoryAdjustment = async (
  payload: InventoryAdjustmentPayload
): Promise<InventoryAdjustment> => {
  const response = await api.post<DataEnvelope<InventoryAdjustment>>(
    '/inventory/adjustments',
    payload
  );

  return response.data.data;
};

export const fetchOrderItemInventoryContext = async (
  orderItemUuid: string
): Promise<InventoryConsumptionContext> => {
  const response = await api.get<DataEnvelope<InventoryConsumptionContext>>(
    `/laboratory-order-items/${orderItemUuid}/inventory-consumptions`
  );

  return response.data.data;
};

export const createOrderItemInventoryConsumption = async (
  orderItemUuid: string,
  payload: InventoryConsumptionPayload
): Promise<InventoryConsumption> => {
  const response = await api.post<DataEnvelope<InventoryConsumption>>(
    `/laboratory-order-items/${orderItemUuid}/inventory-consumptions`,
    payload
  );

  return response.data.data;
};


export const fetchLabTestsForInventory = async (): Promise<LabTest[]> => {
  const response = await api.get<{ data: LabTest[] }>(
    '/lab-tests',
    { params: { page: 1, per_page: 100, is_active: true } }
  );

  return response.data.data;
};

export const fetchLabTestInventoryRequirements = async (
  labTestUuid: string
): Promise<LabTestInventoryRequirementContext> => {
  const response = await api.get<DataEnvelope<LabTestInventoryRequirementContext>>(
    `/lab-tests/${labTestUuid}/inventory-requirements`
  );

  return response.data.data;
};

export const saveLabTestInventoryRequirements = async (
  labTestUuid: string,
  requirements: Array<{
    item_uuid: string;
    quantity: string;
    is_active: boolean;
    notes?: string;
  }>
): Promise<LabTestInventoryRequirementContext> => {
  const response = await api.put<DataEnvelope<LabTestInventoryRequirementContext>>(
    `/lab-tests/${labTestUuid}/inventory-requirements`,
    { requirements }
  );

  return response.data.data;
};
