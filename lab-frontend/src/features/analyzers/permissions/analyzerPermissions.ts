export type AnalyzerAction =
  | 'view_instruments'
  | 'manage_instruments'
  | 'manage_mappings'
  | 'view_imports'
  | 'review_imports'
  | 'reprocess_imports'
  | 'rotate_credentials';

const permissions: Record<AnalyzerAction, string[]> = {
  view_instruments: [
    'superadmin',
    'admin',
    'lab_technician',
    'doctor',
  ],
  manage_instruments: [
    'superadmin',
    'admin',
  ],
  manage_mappings: [
    'superadmin',
    'admin',
  ],
  view_imports: [
    'superadmin',
    'admin',
    'lab_technician',
    'doctor',
  ],
  review_imports: [
    'superadmin',
    'admin',
    'lab_technician',
  ],
  reprocess_imports: [
    'superadmin',
    'admin',
    'lab_technician',
  ],
  rotate_credentials: [
    'superadmin',
    'admin',
  ],
};

export const roleAllowsAnalyzerAction = (
  role: string | undefined,
  action: AnalyzerAction
): boolean => (
  role !== undefined
  && permissions[action].includes(role)
);
