import {
  Box,
  CssBaseline,
  ThemeProvider,
} from '@mui/material';

import {
  QueryClientProvider,
} from '@tanstack/react-query';

import {
  BrowserRouter,
} from 'react-router-dom';

import {
  AppRoutes,
} from './app/AppRoutes';

import AppToaster from
  './app/AppToaster';

import {
  appQueryClient,
} from './app/appQueryClient';

import {
  AuthProvider,
} from './contexts/AuthContext';

import {
  labTheme,
} from './theme/labTheme';

const App = () => (
  <ThemeProvider theme={labTheme}>
    <CssBaseline />

    <QueryClientProvider
      client={appQueryClient}
    >
      <BrowserRouter>
        <AuthProvider>
          <Box sx={{ minHeight: '100vh' }}>
            <AppRoutes />
            <AppToaster />
          </Box>
        </AuthProvider>
      </BrowserRouter>
    </QueryClientProvider>
  </ThemeProvider>
);

export default App;
