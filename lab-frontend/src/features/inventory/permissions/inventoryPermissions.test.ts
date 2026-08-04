import {
  describe,
  expect,
  it,
} from 'vitest';

import {
  roleAllowsInventoryAction,
} from './inventoryPermissions';

describe('inventory permissions', () => {
  it('allows administrators to manage and adjust inventory', () => {
    expect(roleAllowsInventoryAction('admin', 'manage_catalogue')).toBe(true);
    expect(roleAllowsInventoryAction('admin', 'adjust_stock')).toBe(true);
  });

  it('allows laboratory technicians to receive and consume but not adjust', () => {
    expect(roleAllowsInventoryAction('lab_technician', 'view')).toBe(true);
    expect(roleAllowsInventoryAction('lab_technician', 'receive_stock')).toBe(true);
    expect(roleAllowsInventoryAction('lab_technician', 'record_consumption')).toBe(true);
    expect(roleAllowsInventoryAction('lab_technician', 'adjust_stock')).toBe(false);
  });

  it('blocks receptionist inventory access', () => {
    expect(roleAllowsInventoryAction('receptionist', 'view')).toBe(false);
  });
});
