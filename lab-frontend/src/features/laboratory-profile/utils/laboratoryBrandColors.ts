export type LaboratoryBrandColorKey =
  | 'primary_color'
  | 'secondary_color'
  | 'accent_color';

export interface LaboratoryBrandColors {
  primary_color: string;
  secondary_color: string;
  accent_color: string;
}

type BrandColorSource = Partial<
  Record<
    LaboratoryBrandColorKey,
    string | null | undefined
  >
>;

export const DEFAULT_LABORATORY_BRAND_COLORS:
  LaboratoryBrandColors = {
    primary_color: '#1976D2',
    secondary_color: '#424242',
    accent_color: '#2E7D32',
  };

const HEX_COLOR_PATTERN =
  /^#[0-9A-F]{6}$/;

export const isLaboratoryBrandColor = (
  value: string
): boolean => (
  HEX_COLOR_PATTERN.test(
    value.trim().toUpperCase()
  )
);

export const normaliseLaboratoryBrandColor = (
  value: string | null | undefined,
  fallback: string
): string => {
  const normalized =
    value?.trim().toUpperCase() ?? '';

  return isLaboratoryBrandColor(normalized)
    ? normalized
    : fallback;
};

export const resolveLaboratoryBrandColors = (
  source: BrandColorSource
): LaboratoryBrandColors => ({
  primary_color:
    normaliseLaboratoryBrandColor(
      source.primary_color,
      DEFAULT_LABORATORY_BRAND_COLORS
        .primary_color
    ),
  secondary_color:
    normaliseLaboratoryBrandColor(
      source.secondary_color,
      DEFAULT_LABORATORY_BRAND_COLORS
        .secondary_color
    ),
  accent_color:
    normaliseLaboratoryBrandColor(
      source.accent_color,
      DEFAULT_LABORATORY_BRAND_COLORS
        .accent_color
    ),
});

const COLOR_LABELS: Record<
  LaboratoryBrandColorKey,
  string
> = {
  primary_color: 'Primary colour',
  secondary_color: 'Secondary colour',
  accent_color: 'Accent colour',
};

export const laboratoryBrandColorError = (
  key: LaboratoryBrandColorKey,
  value: string
): string | null => {
  if (
    value.trim() === '' ||
    isLaboratoryBrandColor(value)
  ) {
    return null;
  }

  return (
    `${COLOR_LABELS[key]} must use a ` +
    'six-digit HEX value such as #2563EB.'
  );
};

export const laboratoryBrandColorsError = (
  source: BrandColorSource
): string | null => {
  const keys: LaboratoryBrandColorKey[] = [
    'primary_color',
    'secondary_color',
    'accent_color',
  ];

  for (const key of keys) {
    const error = laboratoryBrandColorError(
      key,
      source[key] ?? ''
    );

    if (error) {
      return error;
    }
  }

  return null;
};

export const readableTextColor = (
  backgroundColor: string
): '#000000' | '#FFFFFF' => {
  const color =
    normaliseLaboratoryBrandColor(
      backgroundColor,
      '#000000'
    );

  const red = Number.parseInt(
    color.slice(1, 3),
    16
  );
  const green = Number.parseInt(
    color.slice(3, 5),
    16
  );
  const blue = Number.parseInt(
    color.slice(5, 7),
    16
  );

  const brightness =
    (red * 299 + green * 587 + blue * 114)
    / 1000;

  return brightness >= 150
    ? '#000000'
    : '#FFFFFF';
};