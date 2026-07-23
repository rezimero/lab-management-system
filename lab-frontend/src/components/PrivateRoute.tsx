import {
  Fragment,
  ReactElement,
} from 'react';

import {
  Navigate,
} from 'react-router-dom';

import {
  useAuth,
} from '../contexts/AuthContext';

import ImpersonationControl from './auth/ImpersonationControl';
import AppLoadingScreen from './feedback/AppLoadingScreen';

interface PrivateRouteProps {
  children: ReactElement;
}

const PrivateRoute = ({
  children,
}: PrivateRouteProps): ReactElement => {
  const {
    isAuthenticated,
    isLoading,
    isSuperadmin,
    isImpersonating,
  } = useAuth();

  if (isLoading) {
    return (
      <AppLoadingScreen label="Restoring your secure session" />
    );
  }

  if (!isAuthenticated) {
    return (
      <Navigate
        to="/login"
        replace
      />
    );
  }

  /*
   * A landlord superadmin may only enter tenant
   * routes through the impersonation workflow.
   */
  if (
    isSuperadmin &&
    !isImpersonating
  ) {
    return (
      <Navigate
        to="/landlord"
        replace
      />
    );
  }

  return (
    <Fragment>
      <ImpersonationControl />
      {children}
    </Fragment>
  );
};

export default PrivateRoute;