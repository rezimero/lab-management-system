interface ApiErrorPayload {
  message?: unknown;
  errors?: Record<
    string,
    string | string[]
  >;
}

interface ApiErrorShape {
  response?: {
    data?: ApiErrorPayload;
  };
}

const firstValidationError = (
  errors?: ApiErrorPayload['errors']
): string | null => {
  if (!errors) {
    return null;
  }

  for (const value of Object.values(errors)) {
    if (
      Array.isArray(value) &&
      typeof value[0] === 'string'
    ) {
      return value[0];
    }

    if (typeof value === 'string') {
      return value;
    }
  }

  return null;
};

export const profileErrorMessage = (
  error: unknown,
  fallback: string
): string => {
  if (
    typeof error !== 'object' ||
    error === null
  ) {
    return fallback;
  }

  const response =
    (error as ApiErrorShape).response;

  return (
    firstValidationError(
      response?.data?.errors
    ) ??
    (
      typeof response?.data?.message ===
        'string'
        ? response.data.message
        : fallback
    )
  );
};