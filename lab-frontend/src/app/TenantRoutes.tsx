import {
  Route,
} from 'react-router-dom';

import PrivateRoute from
  '../components/PrivateRoute';

import {
  Dashboard,
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

export const tenantRoutes = (
  <>
    <Route
      path="/dashboard"
      element={
        <PrivateRoute>
          <Dashboard />
        </PrivateRoute>
      }
    />
    <Route
      path="/patients"
      element={
        <PrivateRoute>
          <Patients />
        </PrivateRoute>
      }
    />
    <Route
      path="/patients/new"
      element={
        <PrivateRoute>
          <PatientForm />
        </PrivateRoute>
      }
    />
    <Route
      path="/patients/:id/edit"
      element={
        <PrivateRoute>
          <PatientForm />
        </PrivateRoute>
      }
    />
    <Route
      path="/lab-tests"
      element={
        <PrivateRoute>
          <LabTests />
        </PrivateRoute>
      }
    />
    <Route
      path="/lab-tests/new"
      element={
        <PrivateRoute>
          <LabTestForm />
        </PrivateRoute>
      }
    />
    <Route
      path="/lab-tests/:id/edit"
      element={
        <PrivateRoute>
          <LabTestForm />
        </PrivateRoute>
      }
    />
    <Route
      path="/test-results"
      element={
        <PrivateRoute>
          <TestResults />
        </PrivateRoute>
      }
    />
    <Route
      path="/test-results/new"
      element={
        <PrivateRoute>
          <TestResultForm />
        </PrivateRoute>
      }
    />
    <Route
      path="/test-results/:id/edit"
      element={
        <PrivateRoute>
          <TestResultForm />
        </PrivateRoute>
      }
    />
    <Route
      path="/test-results/:id"
      element={
        <PrivateRoute>
          <TestResultView />
        </PrivateRoute>
      }
    />
    <Route
      path="/laboratory-reports"
      element={
        <PrivateRoute>
          <LaboratoryReports />
        </PrivateRoute>
      }
    />
    <Route
      path="/laboratory-reports/:id"
      element={
        <PrivateRoute>
          <LaboratoryReportView />
        </PrivateRoute>
      }
    />
    <Route
      path="/users"
      element={
        <PrivateRoute>
          <Users />
        </PrivateRoute>
      }
    />
    <Route
      path="/users/new"
      element={
        <PrivateRoute>
          <UserForm />
        </PrivateRoute>
      }
    />
    <Route
      path="/users/:id/edit"
      element={
        <PrivateRoute>
          <UserForm />
        </PrivateRoute>
      }
    />
  </>
);
