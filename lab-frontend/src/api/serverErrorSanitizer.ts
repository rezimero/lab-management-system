export const SERVER_ERROR_MESSAGE =
  'A server error occurred. Please try again. ' +
  'Contact support if the problem continues.';

interface ErrorResponse {
  status?: number;
  data?: unknown;
}

const responseOf = (
  error: unknown
): ErrorResponse | undefined => {
  if (
    typeof error !== 'object' ||
    error === null
  ) {
    return undefined;
  }

  const response = (
    error as {
      response?: unknown;
    }
  ).response;

  if (
    typeof response !== 'object' ||
    response === null
  ) {
    return undefined;
  }

  return response as ErrorResponse;
};

export const sanitizeServerError = <T>(
  error: T
): T => {
  const response = responseOf(error);
  const status = response?.status;

  if (
    !response ||
    typeof status !== 'number' ||
    status < 500
  ) {
    return error;
  }

  response.data = {
    message: SERVER_ERROR_MESSAGE,
  };

  return error;
};