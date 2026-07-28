import {
  describe,
  expect,
  it,
} from 'vitest';

import {
  DEFAULT_LABORATORY_BRAND_COLORS,
  isLaboratoryBrandColor,
  laboratoryBrandColorsError,
  readableTextColor,
  resolveLaboratoryBrandColors,
} from './laboratoryBrandColors';

describe('laboratoryBrandColors', () => {
  it('normalises valid tenant colours', () => {
    expect(
      resolveLaboratoryBrandColors({
        primary_color: '#7c3aed',
        secondary_color: '#0f172a',
        accent_color: '#f59e0b',
      })
    ).toEqual({
      primary_color: '#7C3AED',
      secondary_color: '#0F172A',
      accent_color: '#F59E0B',
    });
  });

  it('uses safe defaults for missing colours', () => {
    expect(
      resolveLaboratoryBrandColors({})
    ).toEqual(
      DEFAULT_LABORATORY_BRAND_COLORS
    );
  });

  it('rejects malformed HEX values', () => {
    expect(
      isLaboratoryBrandColor('#123')
    ).toBe(false);

    expect(
      laboratoryBrandColorsError({
        primary_color: 'blue',
      })
    ).toContain('Primary colour');
  });

  it('selects readable text for accents', () => {
    expect(
      readableTextColor('#FFFFFF')
    ).toBe('#000000');

    expect(
      readableTextColor('#0F172A')
    ).toBe('#FFFFFF');
  });
});