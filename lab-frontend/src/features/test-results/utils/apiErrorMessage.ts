interface ApiErrorShape {
  response?: {
    data?: {
      message?: unknown;
      errors?: Record<string, unknown>;
    };
  };
}

const firstValidationError = (
  errors?: Record<string, unknown>
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

export const apiErrorMessage = (
  error: unknown,
  fallback: string
): string => {
  if (error instanceof Error) {
    const shapedError = error as Error & ApiErrorShape;

    if (!shapedError.response) {
      return error.message || fallback;
    }
  }

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
      typeof response?.data?.message === 'string'
        ? response.data.message
        : fallback
    )
  );
};
