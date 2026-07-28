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
      buildNavigationGroups(true);

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
      buildNavigationGroups(false).some(
        (group) =>
          group.label === 'Administration'
      )
    ).toBe(false);
  });
});