import type { ReactElement } from 'react';
import { Route } from 'react-router-dom';

import PrivateRoute from '../components/PrivateRoute';
import TenantAdminRoute from '../components/TenantAdminRoute';

import {
  Dashboard,
  LaboratoryOrders,
  LaboratoryOrderCreate,
  LaboratoryOrderView,
  LaboratoryProfile,
  LaboratoryReports,
  LaboratoryReportView,
  LabTestForm,
  LabTests,
  PatientForm,
  Patients,
  TestResultForm,
  TestResults,
  TestResultView,
  UserForm,
  Users,
} from './routeComponents';

const protect = (element: ReactElement) => (
  <PrivateRoute>{element}</PrivateRoute>
);

const protectAdministrator = (
  element: ReactElement
) => (
  <PrivateRoute>
    <TenantAdminRoute>
      {element}
    </TenantAdminRoute>
  </PrivateRoute>
);

export const tenantRoutes = (
  <>
    <Route
      path="/dashboard"
      element={protect(<Dashboard />)}
    />
    <Route
      path="/patients"
      element={protect(<Patients />)}
    />
    <Route
      path="/patients/new"
      element={protect(<PatientForm />)}
    />
    <Route
      path="/patients/:id/edit"
      element={protect(<PatientForm />)}
    />
    <Route
      path="/lab-tests"
      element={protect(<LabTests />)}
    />
    <Route
      path="/lab-tests/new"
      element={protect(<LabTestForm />)}
    />
    <Route
      path="/lab-tests/:id/edit"
      element={protect(<LabTestForm />)}
    />
    <Route
      path="/laboratory-orders"
      element={protect(<LaboratoryOrders />)}
    />
    <Route
      path="/laboratory-orders/new"
      element={protect(<LaboratoryOrderCreate />)}
    />
    <Route
      path="/laboratory-orders/:uuid"
      element={protect(<LaboratoryOrderView />)}
    />
    <Route
      path="/test-results"
      element={protect(<TestResults />)}
    />
    <Route
      path="/test-results/new"
      element={protect(<TestResultForm />)}
    />
    <Route
      path="/test-results/:id/edit"
      element={protect(<TestResultForm />)}
    />
    <Route
      path="/test-results/:id"
      element={protect(<TestResultView />)}
    />
    <Route
      path="/laboratory-reports"
      element={protect(<LaboratoryReports />)}
    />
    <Route
      path="/laboratory-reports/:id"
      element={protect(<LaboratoryReportView />)}
    />
    <Route
      path="/settings/laboratory-profile"
      element={protectAdministrator(
        <LaboratoryProfile />
      )}
    />
    <Route
      path="/users"
      element={protect(<Users />)}
    />
    <Route
      path="/users/new"
      element={protect(<UserForm />)}
    />
    <Route
      path="/users/:id/edit"
      element={protect(<UserForm />)}
    />
  </>
);