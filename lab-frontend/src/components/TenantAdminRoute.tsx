import type {
  ReactElement,
} from 'react';

import {
  Navigate,
} from 'react-router-dom';

import {
  useAuth,
} from '../contexts/AuthContext';

interface TenantAdminRouteProps {
  children: ReactElement;
}

const TenantAdminRoute = ({
  children,
}: TenantAdminRouteProps): ReactElement => {
  const {
    user,
  } = useAuth();

  if (user?.role !== 'admin') {
    return (
      <Navigate
        to="/dashboard"
        replace
      />
    );
  }

  return children;
};

export default TenantAdminRoute;