export const formatTestResultDate = (
  value: string | null
): string => {
  if (!value) return '-';

  return new Date(value).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
};

export const testResultPatientInitials = (
  firstName?: string,
  lastName?: string
): string => {
  if (!firstName) return '??';
  return `${firstName[0]}${lastName?.[0] || ''}`.toUpperCase();
};