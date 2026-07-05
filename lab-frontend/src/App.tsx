import React from 'react';
import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { AuthProvider, useAuth } from './contexts/AuthContext';
import Login from './pages/Login';
import Register from './pages/Register';
import Dashboard from './pages/Dashboard';
import Patients from './pages/Patients';
import PatientForm from './pages/PatientForm';
import LabTests from './pages/LabTests';
import LabTestForm from './pages/LabTestForm';
import TestResults from './pages/TestResults';
import TestResultForm from './pages/TestResultForm';
import TestResultView from './pages/TestResultView';
import Users from './pages/Users';
import UserForm from './pages/UserForm';
import LandlordDashboard from './pages/LandlordDashboard';
import PrivateRoute from './components/PrivateRoute';
import { CircularProgress, Box, ThemeProvider, createTheme } from '@mui/material';

const theme = createTheme({
  palette: {
    primary: {
      main: '#4361ee',
      light: '#6c8cff',
      dark: '#2b3faf',
    },
    secondary: {
      main: '#2ec4b6',
      light: '#6df0e0',
      dark: '#009688',
    },
    success: { main: '#06d6a0' },
    warning: { main: '#f8961e' },
    error: { main: '#ef476f' },
    info: { main: '#4361ee' },
    background: {
      default: '#f8fafc',
      paper: '#ffffff',
    },
  },
  shape: { borderRadius: 12 },
  typography: {
    fontFamily: '"Inter", "Segoe UI", "Roboto", sans-serif',
    h1: { fontWeight: 700 },
    h2: { fontWeight: 700 },
    h3: { fontWeight: 700 },
    h4: { fontWeight: 700 },
    h5: { fontWeight: 600 },
    h6: { fontWeight: 600 },
  },
  components: {
    MuiCard: {
      styleOverrides: {
        root: {
          boxShadow: '0 1px 3px rgba(0,0,0,0.04)',
          borderRadius: 16,
        },
      },
    },
    MuiButton: {
      styleOverrides: {
        root: {
          textTransform: 'none',
          fontWeight: 600,
          borderRadius: 10,
        },
      },
    },
    MuiPaper: {
      styleOverrides: {
        root: {
          borderRadius: 16,
        },
      },
    },
  },
});

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      refetchOnWindowFocus: false,
      retry: 1,
    },
  },
});

// Protected route wrapper - uses children as element
const ProtectedRoute: React.FC<{ children: React.ReactElement }> = ({ children }) => {
  const { isAuthenticated, isLoading } = useAuth();

  if (isLoading) {
    return (
      <Box sx={{ display: 'flex', justifyContent: 'center', alignItems: 'center', height: '100vh' }}>
        <CircularProgress />
      </Box>
    );
  }

  if (!isAuthenticated) {
    return <Navigate to="/login" replace />;
  }

  return children;
};

// Superadmin route wrapper
const SuperadminRoute: React.FC<{ children: React.ReactElement }> = ({ children }) => {
  const { isAuthenticated, isLoading, isSuperadmin } = useAuth();

  if (isLoading) {
    return (
      <Box sx={{ display: 'flex', justifyContent: 'center', alignItems: 'center', height: '100vh' }}>
        <CircularProgress />
      </Box>
    );
  }

  if (!isAuthenticated) {
    return <Navigate to="/login" replace />;
  }

  if (!isSuperadmin) {
    return <Navigate to="/dashboard" replace />;
  }

  return children;
};

const AppRoutes: React.FC = () => {
  const { isSuperadmin } = useAuth();

  return (
    <Routes>
      <Route path="/login" element={<Login />} />
      <Route path="/register" element={<Register />} />
      
      <Route path="/" element={<Navigate to={isSuperadmin ? '/landlord' : '/dashboard'} replace />} />
      
      {/* Tenant Routes */}
      <Route path="/dashboard" element={<ProtectedRoute><Dashboard /></ProtectedRoute>} />
      
      <Route path="/patients" element={<ProtectedRoute><Patients /></ProtectedRoute>} />
      <Route path="/patients/new" element={<ProtectedRoute><PatientForm /></ProtectedRoute>} />
      <Route path="/patients/:id/edit" element={<ProtectedRoute><PatientForm /></ProtectedRoute>} />
      
      <Route path="/lab-tests" element={<ProtectedRoute><LabTests /></ProtectedRoute>} />
      <Route path="/lab-tests/new" element={<ProtectedRoute><LabTestForm /></ProtectedRoute>} />
      <Route path="/lab-tests/:id/edit" element={<ProtectedRoute><LabTestForm /></ProtectedRoute>} />
      
      <Route path="/test-results" element={<ProtectedRoute><TestResults /></ProtectedRoute>} />
      <Route path="/test-results/new" element={<ProtectedRoute><TestResultForm /></ProtectedRoute>} />
      <Route path="/test-results/:id/edit" element={<ProtectedRoute><TestResultForm /></ProtectedRoute>} />
      <Route path="/test-results/:id" element={<ProtectedRoute><TestResultView /></ProtectedRoute>} />
      
      <Route path="/users" element={<ProtectedRoute><Users /></ProtectedRoute>} />
      <Route path="/users/new" element={<ProtectedRoute><UserForm /></ProtectedRoute>} />
      <Route path="/users/:id/edit" element={<ProtectedRoute><UserForm /></ProtectedRoute>} />

      {/* Landlord Routes - Superadmin Only */}
      <Route path="/landlord/*" element={<SuperadminRoute><LandlordDashboard /></SuperadminRoute>} />
      
      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  );
};

const App: React.FC = () => {
  return (
    <ThemeProvider theme={theme}>
      <QueryClientProvider client={queryClient}>
        <Router>
          <AuthProvider>
            <AppRoutes />
          </AuthProvider>
        </Router>
      </QueryClientProvider>
    </ThemeProvider>
  );
};

export default App;