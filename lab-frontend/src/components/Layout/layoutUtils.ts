export const getInitials = (
  name?: string
): string => {
  if (!name) {
    return 'R';
  }

  return name
    .trim()
    .split(/\s+/)
    .map((part) => part[0])
    .join('')
    .toUpperCase()
    .slice(0, 2);
};

export const formatRole = (
  role?: string
): string => (
  (role ?? 'user')
    .replace(/_/g, ' ')
    .replace(
      /\w/g,
      (character) =>
        character.toUpperCase()
    )
);
