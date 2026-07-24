import type {
  ChipProps,
} from '@mui/material';

import type {
  TestResultStatus,
} from '../../../types';

export interface StatusVisual {
  label: string;
  color: ChipProps['color'];
  accent: string;
  description: string;
}

const STATUS_VISUALS: Record<
  TestResultStatus,
  StatusVisual
> = {
  draft: {
    label: 'Draft',
    color: 'warning',
    accent: '#DC6803',
    description:
      'The result is still being prepared and may be edited.',
  },
  submitted: {
    label: 'Submitted',
    color: 'info',
    accent: '#1570EF',
    description:
      'The result has been submitted and is awaiting review.',
  },
  approved: {
    label: 'Approved',
    color: 'success',
    accent: '#079455',
    description:
      'The result has completed the authorised review workflow.',
  },
  rejected: {
    label: 'Rejected',
    color: 'error',
    accent: '#D92D20',
    description:
      'The result was returned for correction before approval.',
  },
};

export const statusVisualFor = (
  status: TestResultStatus
): StatusVisual => STATUS_VISUALS[status];

export const formatResultLabel = (
  value?: string | null
): string => {
  if (!value) {
    return 'Not recorded';
  }

  return value
    .replace(/_/g, ' ')
    .replace(
      /\b\w/g,
      (character) =>
        character.toUpperCase()
    );
};

export const formatResultDateTime = (
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

export const resultFlagColor = (
  flag?: string | null
): ChipProps['color'] => {
  const normalized =
    flag?.trim().toLowerCase() ?? '';

  if (
    normalized.includes('critical') ||
    normalized.includes('panic')
  ) {
    return 'error';
  }

  if (
    normalized.includes('high') ||
    normalized.includes('low') ||
    normalized.includes('abnormal')
  ) {
    return 'warning';
  }

  if (
    normalized.includes('normal') ||
    normalized.includes('negative')
  ) {
    return 'success';
  }

  return normalized
    ? 'info'
    : 'default';
};
