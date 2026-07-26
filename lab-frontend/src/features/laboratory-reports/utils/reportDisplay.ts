export const formatReportLabel = (
  value?: string | null
): string => {
  if (!value) {
    return 'Not recorded';
  }

  return value
    .replace(/_/g, ' ')
    .replace(
      /\w/g,
      (character) =>
        character.toUpperCase()
    );
};

export const formatReportDate = (
  value?: string | null
): string => {
  if (!value) {
    return 'Not recorded';
  }

  const date = new Date(value);

  if (Number.isNaN(date.getTime())) {
    return value;
  }

  return new Intl.DateTimeFormat(
    undefined,
    {
      dateStyle: 'medium',
      timeStyle: 'short',
    }
  ).format(date);
};

export const reportErrorMessage = (
  error: unknown,
  fallback: string
): string => {
  if (
    typeof error !== 'object' ||
    error === null
  ) {
    return fallback;
  }

  const response = (
    error as {
      response?: {
        data?: {
          message?: unknown;
          errors?: Record<
            string,
            unknown
          >;
        };
      };
    }
  ).response;

  const message =
    response?.data?.message;

  if (typeof message === 'string') {
    return message;
  }

  const firstError =
    Object.values(
      response?.data?.errors ?? {}
    )[0];

  if (
    Array.isArray(firstError) &&
    typeof firstError[0] === 'string'
  ) {
    return firstError[0];
  }

  if (typeof firstError === 'string') {
    return firstError;
  }

  return fallback;
};
