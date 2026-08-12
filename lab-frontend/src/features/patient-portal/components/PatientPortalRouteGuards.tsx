import type {
  ReactElement,
} from 'react';

import {
  Navigate,
  useLocation,
} from 'react-router-dom';

import AppLoadingScreen from
  '../../../components/feedback/AppLoadingScreen';

import {
  usePatientPortalAuth,
} from '../context/PatientPortalAuthContext';

interface GuardProps {
  children: ReactElement;
}

export const PatientPortalProtectedRoute = ({
  children,
}: GuardProps): ReactElement => {
  const auth = usePatientPortalAuth();
  const location = useLocation();

  if (auth.isLoading) {
    return (
      <AppLoadingScreen
        label="Checking your patient portal session"
      />
    );
  }

  if (!auth.isAuthenticated) {
    return (
      <Navigate
        to="/patient-portal/login"
        replace
        state={{
          from: location.pathname,
        }}
      />
    );
  }

  return children;
};

export const PatientPortalPublicRoute = ({
  children,
}: GuardProps): ReactElement => {
  const auth = usePatientPortalAuth();

  if (auth.isLoading) {
    return (
      <AppLoadingScreen
        label="Checking your patient portal session"
      />
    );
  }

  if (auth.isAuthenticated) {
    return (
      <Navigate
        to="/patient-portal"
        replace
      />
    );
  }

  return children;
};
