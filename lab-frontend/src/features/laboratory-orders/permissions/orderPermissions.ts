export type OrderPermission =
  | 'create_result'
  | 'generate_report'
  | 'manage_specimens'
  | 'manage_order_status';

type Role = string | null | undefined;

const permissionRoles: Record<
  OrderPermission,
  string[]
> = {
  create_result: [
    'superadmin',
    'admin',
    'lab_technician',
  ],
  generate_report: [
    'superadmin',
    'admin',
    'lab_technician',
    'doctor',
  ],
  manage_specimens: [
    'superadmin',
    'admin',
    'lab_technician',
  ],
  manage_order_status: [
    'superadmin',
    'admin',
    'lab_technician',
  ],
};

export const roleAllowsOrderAction = (
  role: Role,
  permission: OrderPermission
): boolean => (
  Boolean(role)
  && permissionRoles[permission].includes(
    String(role)
  )
);
