const recordOf = (
  value: unknown
): Record<string, unknown> | null => (
  typeof value === 'object' && value !== null
    ? value as Record<string, unknown>
    : null
);

export const patientPortalErrorMessage = (
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

    if (message) {
      return message;
    }
  }

  return typeof data?.message === 'string'
    ? data.message
    : fallback;
};

export const formatPortalDate = (
  value?: string | null,
  withTime = true
): string => {
  if (!value) {
    return 'Not recorded';
  }

  const date = new Date(value);

  if (Number.isNaN(date.getTime())) {
    return value;
  }

  return new Intl.DateTimeFormat(
    'en-GB',
    withTime
      ? {
          dateStyle: 'medium',
          timeStyle: 'short',
        }
      : {
          dateStyle: 'medium',
        }
  ).format(date);
};

export const formatPortalLabel = (
  value?: string | null
): string => {
  if (!value) {
    return 'Not recorded';
  }

  return value
    .replace(/_/g, ' ')
    .replace(/\b\w/g, (letter) => (
      letter.toUpperCase()
    ));
};

export const patientDisplayName = (
  firstName?: string | null,
  lastName?: string | null,
  fullName?: string | null
): string => {
  const provided = fullName?.trim();

  if (provided) {
    return provided;
  }

  const composed = [
    firstName?.trim(),
    lastName?.trim(),
  ]
    .filter(Boolean)
    .join(' ');

  return composed || 'Patient';
};

export const extractPdfFilename = (
  contentDisposition: string | undefined,
  fallback: string
): string => {
  if (!contentDisposition) {
    return fallback;
  }

  const utfMatch =
    contentDisposition.match(
      /filename\*=UTF-8''([^;]+)/i
    );

  if (utfMatch?.[1]) {
    try {
      return decodeURIComponent(
        utfMatch[1].replace(/["']/g, '')
      );
    } catch {
      return fallback;
    }
  }

  const basicMatch =
    contentDisposition.match(
      /filename="?([^";]+)"?/i
    );

  return basicMatch?.[1]?.trim()
    || fallback;
};

export const savePortalBlob = (
  blob: Blob,
  filename: string
): void => {
  const url = URL.createObjectURL(blob);
  const anchor =
    document.createElement('a');

  anchor.href = url;
  anchor.download = filename;
  anchor.style.display = 'none';

  document.body.appendChild(anchor);
  anchor.click();
  anchor.remove();

  URL.revokeObjectURL(url);
};
