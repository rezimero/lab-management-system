import {
  lazy,
  ReactElement,
  Suspense,
} from 'react';

import {
  Box,
  CssBaseline,
  ThemeProvider,
} from '@mui/material';

import {
  QueryClient,
  QueryClientProvider,
} from '@tanstack/react-query';

import {
  BrowserRouter,
  Navigate,
  Route,
  Routes,
} from 'react-router-dom';

import { Toaster } from 'react-hot-toast';

import PrivateRoute from './components/PrivateRoute';
import AppLoadingScreen from './components/feedback/AppLoadingScreen';

import {
  AuthProvider,
  useAuth,
} from './contexts/AuthContext';

import Login from './pages/Login';
import Register from './pages/Register';

import { labTheme } from './theme/labTheme';

const Dashboard = lazy(() => import('./pages/Dashboard'));
const Patients = lazy(() => import('./pages/Patients'));
const PatientForm = lazy(() => import('./pages/PatientForm'));
const LabTests = lazy(() => import('./pages/LabTests'));
const LabTestForm = lazy(() => import('./pages/LabTestForm'));
const TestResults = lazy(() => import('./pages/TestResults'));

const TestResultForm = lazy(
  () => import('./pages/TestResultForm')
);

const TestResultView = lazy(
  () => import('./pages/TestResultView')
);

const Users = lazy(() => import('./pages/Users'));
const UserForm = lazy(() => import('./pages/UserForm'));

const LandlordDashboard = lazy(
  () => import('./pages/LandlordDashboard')
);

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 30_000,
      gcTime: 5 * 60_000,
      refetchOnWindowFocus: false,
      retry: 1,
    },

    mutations: {
      retry: 0,
    },
  },
});

interface RouteGuardProps {
  children: ReactElement;
}

const PublicOnlyRoute = ({
  children,
}: RouteGuardProps): ReactElement => {
  const {
    isAuthenticated,
    isLoading,
    isSuperadmin,
  } = useAuth();

  if (isLoading) {
    return (
      <AppLoadingScreen label="Checking your session" />
    );
  }

  if (isAuthenticated) {
    return (
      <Navigate
        to={isSuperadmin ? '/landlord' : '/dashboard'}
        replace
      />
    );
  }

  return children;
};

const SuperadminRoute = ({
  children,
}: RouteGuardProps): ReactElement => {
  const {
    isAuthenticated,
    isLoading,
    isSuperadmin,
  } = useAuth();

  if (isLoading) {
    return (
      <AppLoadingScreen label="Verifying platform access" />
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

  if (!isSuperadmin) {
    return (
      <Navigate
        to="/dashboard"
        replace
      />
    );
  }

  return children;
};

const HomeRedirect = (): ReactElement => {
  const {
    isAuthenticated,
    isLoading,
    isSuperadmin,
  } = useAuth();

  if (isLoading) {
    return (
      <AppLoadingScreen label="Opening your workspace" />
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

  return (
    <Navigate
      to={isSuperadmin ? '/landlord' : '/dashboard'}
      replace
    />
  );
};

const AppRoutes = (): ReactElement => {
  return (
    <Suspense fallback={<AppLoadingScreen />}>
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
};

const App = (): ReactElement => {
  return (
    <ThemeProvider theme={labTheme}>
      <CssBaseline />

      <QueryClientProvider client={queryClient}>
        <BrowserRouter>
          <AuthProvider>
            <Box sx={{ minHeight: '100vh' }}>
              <AppRoutes />

              <Toaster
                position="top-right"
                gutter={10}
                toastOptions={{
                  duration: 4500,

                  style: {
                    maxWidth: 420,
                    padding: '14px 16px',
                    borderRadius: 14,
                    color: '#0B1220',
                    background: '#FFFFFF',
                    border:
                      '1px solid rgba(11, 18, 32, 0.08)',
                    boxShadow:
                      '0 18px 48px rgba(11, 18, 32, 0.14)',
                    fontSize: '0.875rem',
                    fontWeight: 540,
                  },

                  success: {
                    iconTheme: {
                      primary: '#079455',
                      secondary: '#FFFFFF',
                    },
                  },

                  error: {
                    duration: 6000,

                    iconTheme: {
                      primary: '#D92D20',
                      secondary: '#FFFFFF',
                    },
                  },
                }}
              />
            </Box>
          </AuthProvider>
        </BrowserRouter>
      </QueryClientProvider>
    </ThemeProvider>
  );
};

export default App;