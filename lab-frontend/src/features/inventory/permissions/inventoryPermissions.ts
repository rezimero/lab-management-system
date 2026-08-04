export type InventoryAction =
  | 'view'
  | 'manage_catalogue'
  | 'receive_stock'
  | 'adjust_stock'
  | 'record_consumption';

const permissions: Record<InventoryAction, string[]> = {
  view: ['superadmin', 'admin', 'lab_technician'],
  manage_catalogue: ['superadmin', 'admin'],
  receive_stock: ['superadmin', 'admin', 'lab_technician'],
  adjust_stock: ['superadmin', 'admin'],
  record_consumption: ['superadmin', 'admin', 'lab_technician'],
};

export const roleAllowsInventoryAction = (
  role: string | undefined,
  action: InventoryAction
): boolean => permissions[action].includes(role ?? '');
