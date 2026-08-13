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
  useClinicianPortalAuth,
} from '../context/ClinicianPortalAuthContext';

interface GuardProps {
  children: ReactElement;
}

export const ClinicianPortalProtectedRoute = ({
  children,
}: GuardProps): ReactElement => {
  const auth = useClinicianPortalAuth();
  const location = useLocation();

  if (auth.isLoading) {
    return (
      <AppLoadingScreen
        label="Checking your clinician portal session"
      />
    );
  }

  if (!auth.isAuthenticated) {
    return (
      <Navigate
        to="/clinician-portal/login"
        replace
        state={{
          from: location.pathname,
        }}
      />
    );
  }

  return children;
};

export const ClinicianPortalPublicRoute = ({
  children,
}: GuardProps): ReactElement => {
  const auth = useClinicianPortalAuth();

  if (auth.isLoading) {
    return (
      <AppLoadingScreen
        label="Checking your clinician portal session"
      />
    );
  }

  if (auth.isAuthenticated) {
    return (
      <Navigate
        to="/clinician-portal"
        replace
      />
    );
  }

  return children;
};
