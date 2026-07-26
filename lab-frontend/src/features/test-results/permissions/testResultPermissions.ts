export type TestResultPermission =
  | 'edit_test_results'
  | 'approve_test_results';

const ROLE_PERMISSIONS: Record<
  string,
  readonly TestResultPermission[]
> = {
  superadmin: [
    'edit_test_results',
    'approve_test_results',
  ],
  admin: [
    'edit_test_results',
    'approve_test_results',
  ],
  lab_technician: [
    'edit_test_results',
  ],
  doctor: [
    'approve_test_results',
  ],
};

export const roleAllowsTestResult = (
  role: string | null | undefined,
  permission: TestResultPermission
): boolean => (
  Boolean(role) &&
  (
    ROLE_PERMISSIONS[role ?? ''] ??
    []
  ).includes(permission)
);
