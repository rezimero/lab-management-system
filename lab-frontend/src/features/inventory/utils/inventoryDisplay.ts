export const inventoryQuantity = (
  value: string | number,
  symbol?: string | null
): string => {
  const numeric = Number(value || 0);
  const rendered = new Intl.NumberFormat('en-NG', {
    maximumFractionDigits: 6,
  }).format(numeric);

  return symbol ? `${rendered} ${symbol}` : rendered;
};

export const inventoryDate = (
  value?: string | null
): string => {
  if (!value) return '—';

  return new Intl.DateTimeFormat('en-NG', {
    dateStyle: 'medium',
    timeStyle: value.includes('T') ? 'short' : undefined,
  }).format(new Date(value));
};

export const localDateTimeInput = (): string => {
  const now = new Date();
  now.setMinutes(now.getMinutes() - now.getTimezoneOffset());

  return now.toISOString().slice(0, 16);
};
