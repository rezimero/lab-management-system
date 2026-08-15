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

import PatientPortalRoutes from
  '../features/patient-portal/routes/PatientPortalRoutes';

import ClinicianPortalRoutes from
  '../features/clinician-portal/routes/ClinicianPortalRoutes';

import Login from '../pages/Login';
import Register from '../pages/Register';

import {
  HomeRedirect,
  PublicOnlyRoute,
  SuperadminRoute,
} from './RouteGuards';

import {
  LandlordDashboard,
  PortalActivation,
  PortalPasswordReset,
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
        path="/portal/activate"
        element={<PortalActivation />}
      />

      <Route
        path="/portal/reset-password"
        element={<PortalPasswordReset />}
      />

      <Route
        path="/patient-portal/*"
        element={<PatientPortalRoutes />}
      />

      <Route
        path="/clinician-portal/*"
        element={<ClinicianPortalRoutes />}
      />

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
