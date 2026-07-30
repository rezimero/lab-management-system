const defaultPdfFilename =
  'laboratory-report.pdf';

const safePdfFilename = (
  value: string
): string => {
  const cleaned = value
    .replace(/[\/\\]+/g, '-')
    .replace(
      /[\u0000-\u001F\u007F"<>:|?*]+/g,
      '-'
    )
    .replace(/\s+/g, ' ')
    .replace(/^\.+/, '')
    .trim();

  if (!cleaned) {
    return defaultPdfFilename;
  }

  return cleaned.toLowerCase().endsWith(
    '.pdf'
  )
    ? cleaned
    : `${cleaned}.pdf`;
};

const decodeFilename = (
  value: string
): string => {
  try {
    return decodeURIComponent(value);
  } catch {
    return value;
  }
};

export const reportPdfFilename = (
  contentDisposition?: string,
  reportNumber?: string
): string => {
  const fallback = safePdfFilename(
    reportNumber?.trim() ||
      defaultPdfFilename
  );

  if (!contentDisposition) {
    return fallback;
  }

  const encodedMatch =
    /filename\*\s*=\s*UTF-8''([^;]+)/i
      .exec(contentDisposition);

  if (encodedMatch?.[1]) {
    return safePdfFilename(
      decodeFilename(
        encodedMatch[1]
          .trim()
          .replace(/^"|"$/g, '')
      )
    );
  }

  const quotedMatch =
    /filename\s*=\s*"([^"]+)"/i
      .exec(contentDisposition);

  if (quotedMatch?.[1]) {
    return safePdfFilename(
      quotedMatch[1]
    );
  }

  const plainMatch =
    /filename\s*=\s*([^;]+)/i
      .exec(contentDisposition);

  return plainMatch?.[1]
    ? safePdfFilename(
        plainMatch[1].trim()
      )
    : fallback;
};

export const downloadBlob = (
  blob: Blob,
  filename: string
): void => {
  const objectUrl =
    URL.createObjectURL(blob);

  const anchor =
    document.createElement('a');

  anchor.href = objectUrl;
  anchor.download =
    safePdfFilename(filename);
  anchor.style.display = 'none';

  document.body.appendChild(anchor);

  try {
    anchor.click();
  } finally {
    anchor.remove();
    URL.revokeObjectURL(objectUrl);
  }
};