export type ReportPermission =
  | 'view'
  | 'generate';

const ROLE_PERMISSIONS: Record<
  string,
  readonly ReportPermission[]
> = {
  superadmin: [
    'view',
    'generate',
  ],
  admin: [
    'view',
    'generate',
  ],
  lab_technician: [
    'view',
    'generate',
  ],
  receptionist: [
    'view',
  ],
  doctor: [
    'view',
    'generate',
  ],
};

export const roleAllowsReport = (
  role: string | null | undefined,
  permission: ReportPermission
): boolean => (
  Boolean(role) &&
  (
    ROLE_PERMISSIONS[role ?? ''] ??
    []
  ).includes(permission)
);
