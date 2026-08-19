import {
  describe,
  expect,
  it,
} from 'vitest';

import {
  roleAllowsAnalyzerAction,
} from './analyzerPermissions';

describe('analyzer permissions', () => {
  it('allows administrators to configure analyzers', () => {
    expect(
      roleAllowsAnalyzerAction(
        'admin',
        'manage_instruments'
      )
    ).toBe(true);

    expect(
      roleAllowsAnalyzerAction(
        'admin',
        'manage_mappings'
      )
    ).toBe(true);
  });

  it('allows laboratory technicians to review imports but not configure devices', () => {
    expect(
      roleAllowsAnalyzerAction(
        'lab_technician',
        'review_imports'
      )
    ).toBe(true);

    expect(
      roleAllowsAnalyzerAction(
        'lab_technician',
        'manage_instruments'
      )
    ).toBe(false);
  });

  it('allows doctors read-only analyzer visibility', () => {
    expect(
      roleAllowsAnalyzerAction(
        'doctor',
        'view_imports'
      )
    ).toBe(true);

    expect(
      roleAllowsAnalyzerAction(
        'doctor',
        'review_imports'
      )
    ).toBe(false);
  });

  it('denies receptionist analyzer access', () => {
    expect(
      roleAllowsAnalyzerAction(
        'receptionist',
        'view_instruments'
      )
    ).toBe(false);

    expect(
      roleAllowsAnalyzerAction(
        'receptionist',
        'view_imports'
      )
    ).toBe(false);
  });
});
