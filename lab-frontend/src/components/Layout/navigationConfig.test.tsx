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
      ])
    );
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

});
