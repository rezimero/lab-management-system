import type {
  ReactElement,
} from 'react';

import {
  Navigate,
} from 'react-router-dom';

import AppLoadingScreen from
  '../components/feedback/AppLoadingScreen';

import {
  useAuth,
} from '../contexts/AuthContext';

interface Props {
  children: ReactElement;
}

export const PublicOnlyRoute = ({
  children,
}: Props): ReactElement => {
  const auth = useAuth();

  if (auth.isLoading) {
    return (
      <AppLoadingScreen
        label="Checking your session"
      />
    );
  }

  if (auth.isAuthenticated) {
    return (
      <Navigate
        to={
          auth.isSuperadmin
            ? '/landlord'
            : '/dashboard'
        }
        replace
      />
    );
  }

  return children;
};

export const SuperadminRoute = ({
  children,
}: Props): ReactElement => {
  const auth = useAuth();

  if (auth.isLoading) {
    return (
      <AppLoadingScreen
        label="Verifying platform access"
      />
    );
  }

  if (!auth.isAuthenticated) {
    return (
      <Navigate
        to="/login"
        replace
      />
    );
  }

  if (!auth.isSuperadmin) {
    return (
      <Navigate
        to="/dashboard"
        replace
      />
    );
  }

  return children;
};

export const HomeRedirect = () => {
  const auth = useAuth();

  if (auth.isLoading) {
    return (
      <AppLoadingScreen
        label="Opening your workspace"
      />
    );
  }

  if (!auth.isAuthenticated) {
    return (
      <Navigate
        to="/login"
        replace
      />
    );
  }

  return (
    <Navigate
      to={
        auth.isSuperadmin
          ? '/landlord'
          : '/dashboard'
      }
      replace
    />
  );
};
