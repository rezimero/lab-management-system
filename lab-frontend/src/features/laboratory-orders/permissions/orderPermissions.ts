export type OrderPermission =
  | 'create_result'
  | 'generate_report';

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
