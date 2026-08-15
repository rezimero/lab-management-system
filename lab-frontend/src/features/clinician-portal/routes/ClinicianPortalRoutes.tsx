import {
  Navigate,
  Route,
  Routes,
} from 'react-router-dom';

import ClinicianPortalShell from
  '../components/ClinicianPortalShell';

import {
  ClinicianPortalProtectedRoute,
  ClinicianPortalPublicRoute,
} from '../components/ClinicianPortalRouteGuards';

import ClinicianPortalDashboard from
  '../pages/ClinicianPortalDashboard';

import ClinicianPortalLogin from
  '../pages/ClinicianPortalLogin';

import ClinicianPortalOrderDetail from
  '../pages/ClinicianPortalOrderDetail';

import ClinicianPortalOrders from
  '../pages/ClinicianPortalOrders';

import ClinicianPortalPatients from
  '../pages/ClinicianPortalPatients';

import ClinicianPortalReportDetail from
  '../pages/ClinicianPortalReportDetail';

import ClinicianPortalReports from
  '../pages/ClinicianPortalReports';

import ClinicianPortalNotifications from
  '../pages/ClinicianPortalNotifications';

import {
  ClinicianPortalAuthProvider,
} from '../context/ClinicianPortalAuthContext';

const ClinicianPortalRoutes = () => (
  <ClinicianPortalAuthProvider>
    <Routes>
    <Route
      path="login"
      element={
        <ClinicianPortalPublicRoute>
          <ClinicianPortalLogin />
        </ClinicianPortalPublicRoute>
      }
    />

    <Route
      element={
        <ClinicianPortalProtectedRoute>
          <ClinicianPortalShell />
        </ClinicianPortalProtectedRoute>
      }
    >
      <Route
        index
        element={<ClinicianPortalDashboard />}
      />

      <Route
        path="patients"
        element={<ClinicianPortalPatients />}
      />

      <Route
        path="orders"
        element={<ClinicianPortalOrders />}
      />

      <Route
        path="orders/:orderUuid"
        element={<ClinicianPortalOrderDetail />}
      />

      <Route
        path="reports"
        element={<ClinicianPortalReports />}
      />

      <Route
        path="reports/:reportUuid"
        element={<ClinicianPortalReportDetail />}
      />

      <Route
        path="notifications"
        element={<ClinicianPortalNotifications />}
      />
    </Route>

    <Route
      path="*"
      element={
        <Navigate
          to="/clinician-portal"
          replace
        />
      }
    />
    </Routes>
  </ClinicianPortalAuthProvider>
);

export default ClinicianPortalRoutes;
