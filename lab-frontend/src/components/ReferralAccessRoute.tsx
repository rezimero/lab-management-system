import type { ReactElement } from 'react';

import PrivateRoute from './PrivateRoute';

interface ReferralAccessRouteProps {
  children: ReactElement;
}

export default function ReferralAccessRoute({
  children,
}: ReferralAccessRouteProps) {
  return (
    <PrivateRoute>
      {children}
    </PrivateRoute>
  );
}
