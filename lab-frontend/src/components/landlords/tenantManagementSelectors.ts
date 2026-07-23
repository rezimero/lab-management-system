import type {
  TenantRecord,
  TenantTotals,
} from './tenantManagementTypes';

export const safeNumber = (
  value: unknown
): number => {
  const parsedValue = Number(value);

  return Number.isFinite(parsedValue)
    ? parsedValue
    : 0;
};

export const filterTenants = (
  tenants: TenantRecord[],
  search: string
): TenantRecord[] => {
  const query =
    search.trim().toLowerCase();

  if (!query) {
    return tenants;
  }

  return tenants.filter(
    (tenant) =>
      tenant.name
        .toLowerCase()
        .includes(query) ||
      tenant.domain
        .toLowerCase()
        .includes(query) ||
      (tenant.database ?? '')
        .toLowerCase()
        .includes(query)
  );
};

export const calculateTenantTotals = (
  tenants: TenantRecord[]
): TenantTotals => {
  return tenants.reduce(
    (
      accumulator,
      tenant
    ) => {
      accumulator.users +=
        safeNumber(
          tenant.stats?.users
        );

      accumulator.patients +=
        safeNumber(
          tenant.stats?.patients
        );

      accumulator.tests +=
        safeNumber(
          tenant.stats?.lab_tests
        );

      accumulator.results +=
        safeNumber(
          tenant.stats?.test_results
        );

      return accumulator;
    },
    {
      users: 0,
      patients: 0,
      tests: 0,
      results: 0,
    }
  );
};

export const countActiveTenants = (
  tenants: TenantRecord[]
): number => {
  return tenants.filter(
    (tenant) => tenant.is_active
  ).length;
};
