export type ReferralPermission =
  | "view"
  | "manage_directory"
  | "create"
  | "dispatch"
  | "capture_results";

const ROLE_PERMISSIONS: Record<
  string,
  readonly ReferralPermission[]
> = {
  superadmin: [
    "view",
    "manage_directory",
    "create",
    "dispatch",
    "capture_results",
  ],
  admin: [
    "view",
    "manage_directory",
    "create",
    "dispatch",
    "capture_results",
  ],
  lab_technician: [
    "view",
    "create",
    "dispatch",
    "capture_results",
  ],
  receptionist: [
    "view",
  ],
  doctor: [
    "view",
  ],
};

export const roleAllowsReferralAction = (
  role: string | null | undefined,
  permission: ReferralPermission,
): boolean => (
  Boolean(role) &&
  (
    ROLE_PERMISSIONS[
      role ?? ""
    ] ?? []
  ).includes(permission)
);

export const canViewReferrals = (
  role?: string,
): boolean =>
  roleAllowsReferralAction(
    role,
    "view",
  );
