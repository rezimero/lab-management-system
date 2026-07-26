import {
  Suspense,
} from 'react';

import {
  Navigate,
  Route,
  Routes,
} from 'react-router-dom';

import AppLoadingScreen from
  '../components/feedback/AppLoadingScreen';

import Login from '../pages/Login';
import Register from '../pages/Register';

import {
  HomeRedirect,
  PublicOnlyRoute,
  SuperadminRoute,
} from './RouteGuards';

import {
  LandlordDashboard,
} from './routeComponents';

import {
  tenantRoutes,
} from './TenantRoutes';

export const AppRoutes = () => (
  <Suspense
    fallback={<AppLoadingScreen />}
  >
    <Routes>
      <Route
        path="/login"
        element={
          <PublicOnlyRoute>
            <Login />
          </PublicOnlyRoute>
        }
      />

      <Route
        path="/register"
        element={
          <PublicOnlyRoute>
            <Register />
          </PublicOnlyRoute>
        }
      />

      <Route
        path="/"
        element={<HomeRedirect />}
      />

      {tenantRoutes}

      <Route
        path="/landlord/*"
        element={
          <SuperadminRoute>
            <LandlordDashboard />
          </SuperadminRoute>
        }
      />

      <Route
        path="*"
        element={
          <Navigate
            to="/"
            replace
          />
        }
      />
    </Routes>
  </Suspense>
);
