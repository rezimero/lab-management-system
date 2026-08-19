import {
  describe,
  expect,
  it,
} from 'vitest';

import {
  buildNavigationGroups,
} from './navigationConfig';

describe('laboratory profile navigation', () => {
  it('shows profile settings to administrators', () => {
    const groups =
      buildNavigationGroups(true, 'admin');

    const administration = groups.find(
      (group) =>
        group.label === 'Administration'
    );

    expect(
      administration?.items
    ).toEqual(
      expect.arrayContaining([
        expect.objectContaining({
          label: 'Laboratory profile',
          path:
            '/settings/laboratory-profile',
        }),
        expect.objectContaining({
          label: 'Referring clinicians',
          path:
            '/settings/referring-clinicians',
        }),
        expect.objectContaining({
          label: 'Portal access',
          path: '/settings/portal-access',
        }),
      ])
    );

    expect(
      groups
        .flatMap((group) => group.items)
        .some((item) => item.path === '/notifications')
    ).toBe(true);
  });

  it('hides administration from other roles', () => {
    expect(
      buildNavigationGroups(false, 'lab_technician').some(
        (group) =>
          group.label === 'Administration'
      )
    ).toBe(false);
  });


  it('shows branch operations to tenant users', () => {
    const groups = buildNavigationGroups(
      false,
      'lab_technician'
    );

    const commercial = groups.find(
      (group) =>
        group.label === 'Commercial operations'
    );

    expect(commercial?.items).toEqual(
      expect.arrayContaining([
        expect.objectContaining({
          label: 'Branch network',
          path: '/branches',
        }),
      ])
    );
  });

  it('shows billing only to authorised roles', () => {
    const receptionistGroups =
      buildNavigationGroups(
        false,
        'receptionist'
      );

    const technicianGroups =
      buildNavigationGroups(
        false,
        'lab_technician'
      );

    expect(
      receptionistGroups
        .flatMap((group) => group.items)
        .some((item) => item.path === '/billing')
    ).toBe(true);

    expect(
      technicianGroups
        .flatMap((group) => group.items)
        .some((item) => item.path === '/billing')
    ).toBe(false);
  });

  it('shows inventory only when role and entitlement allow it', () => {
    const enabled = buildNavigationGroups(
      false,
      'lab_technician',
      true
    );

    const disabled = buildNavigationGroups(
      false,
      'lab_technician',
      false
    );

    const receptionist = buildNavigationGroups(
      false,
      'receptionist',
      true
    );

    expect(
      enabled.flatMap((group) => group.items)
        .some((item) => item.path === '/inventory')
    ).toBe(true);

    expect(
      disabled.flatMap((group) => group.items)
        .some((item) => item.path === '/inventory')
    ).toBe(false);

    expect(
      receptionist.flatMap((group) => group.items)
        .some((item) => item.path === '/inventory')
    ).toBe(false);
  });


  it('shows analyzer workspaces only when entitlement and role allow them', () => {
    const enabled = buildNavigationGroups(
      false,
      'lab_technician',
      false,
      true
    );

    const disabled = buildNavigationGroups(
      false,
      'lab_technician',
      false,
      false
    );

    const receptionist = buildNavigationGroups(
      false,
      'receptionist',
      false,
      true
    );

    expect(
      enabled
        .flatMap((group) => group.items)
        .some((item) => item.path === '/analyzers')
    ).toBe(true);

    expect(
      enabled
        .flatMap((group) => group.items)
        .some((item) => item.path === '/analyzer-imports')
    ).toBe(true);

    expect(
      disabled
        .flatMap((group) => group.items)
        .some((item) => item.path === '/analyzers')
    ).toBe(false);

    expect(
      receptionist
        .flatMap((group) => group.items)
        .some((item) => item.path === '/analyzer-imports')
    ).toBe(false);
  });

});
