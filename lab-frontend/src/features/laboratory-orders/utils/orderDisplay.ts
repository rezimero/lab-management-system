const recordOf = (
  value: unknown
): Record<string, unknown> | null => (
  typeof value === 'object' && value !== null
    ? value as Record<string, unknown>
    : null
);

export const orderErrorMessage = (
  error: unknown,
  fallback: string
): string => {
  const root = recordOf(error);
  const response = recordOf(root?.response);
  const data = recordOf(response?.data);
  const errors = recordOf(data?.errors);
  const firstError = errors
    ? Object.values(errors).find(Array.isArray)
    : null;

  if (Array.isArray(firstError)) {
    const message = firstError.find(
      (value): value is string => (
        typeof value === 'string'
      )
    );
    if (message) return message;
  }

  return typeof data?.message === 'string'
    ? data.message
    : fallback;
};

export const formatOrderDate = (
  value?: string | null
): string => {
  if (!value) return 'Not recorded';
  const date = new Date(value);
  return Number.isNaN(date.getTime())
    ? value
    : new Intl.DateTimeFormat('en-GB', {
        dateStyle: 'medium',
        timeStyle: 'short',
      }).format(date);
};

export const formatOrderLabel = (
  value?: string | null
): string => {
  if (!value) return 'Not recorded';
  return value
    .replace(/_/g, ' ')
    .replace(/\w/g, (letter) => (
      letter.toUpperCase()
    ));
};

export const formatOrderAmount = (
  value: string | number
): string => {
  const amount = Number(value);
  return Number.isFinite(amount)
    ? new Intl.NumberFormat('en-GB', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      }).format(amount)
    : String(value);
};
