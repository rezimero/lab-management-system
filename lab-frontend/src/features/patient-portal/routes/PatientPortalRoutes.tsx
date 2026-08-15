import {
  Navigate,
  Route,
  Routes,
} from 'react-router-dom';

import PatientPortalShell from
  '../components/PatientPortalShell';

import {
  PatientPortalProtectedRoute,
  PatientPortalPublicRoute,
} from '../components/PatientPortalRouteGuards';

import PatientPortalDashboard from
  '../pages/PatientPortalDashboard';

import PatientPortalLogin from
  '../pages/PatientPortalLogin';

import PatientPortalOrderDetail from
  '../pages/PatientPortalOrderDetail';

import PatientPortalOrders from
  '../pages/PatientPortalOrders';

import PatientPortalReportDetail from
  '../pages/PatientPortalReportDetail';

import PatientPortalReports from
  '../pages/PatientPortalReports';

import PatientPortalNotifications from
  '../pages/PatientPortalNotifications';

import {
  PatientPortalAuthProvider,
} from '../context/PatientPortalAuthContext';

const PatientPortalRoutes = () => (
  <PatientPortalAuthProvider>
    <Routes>
    <Route
      path="login"
      element={
        <PatientPortalPublicRoute>
          <PatientPortalLogin />
        </PatientPortalPublicRoute>
      }
    />

    <Route
      element={
        <PatientPortalProtectedRoute>
          <PatientPortalShell />
        </PatientPortalProtectedRoute>
      }
    >
      <Route
        index
        element={<PatientPortalDashboard />}
      />

      <Route
        path="orders"
        element={<PatientPortalOrders />}
      />

      <Route
        path="orders/:orderUuid"
        element={<PatientPortalOrderDetail />}
      />

      <Route
        path="reports"
        element={<PatientPortalReports />}
      />

      <Route
        path="reports/:reportUuid"
        element={<PatientPortalReportDetail />}
      />

      <Route
        path="notifications"
        element={<PatientPortalNotifications />}
      />
    </Route>

    <Route
      path="*"
      element={
        <Navigate
          to="/patient-portal"
          replace
        />
      }
    />
    </Routes>
  </PatientPortalAuthProvider>
);

export default PatientPortalRoutes;
