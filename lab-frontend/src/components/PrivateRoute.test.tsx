import {
  render,
  screen,
} from '@testing-library/react';

import {
  MemoryRouter,
  Route,
  Routes,
} from 'react-router-dom';

import {
  beforeEach,
  describe,
  expect,
  it,
  vi,
} from 'vitest';

import {
  AuthContextType,
  useAuth,
} from '../contexts/AuthContext';

import PrivateRoute from './PrivateRoute';

vi.mock(
  '../contexts/AuthContext',
  () => ({
    useAuth: vi.fn(),
  })
);

vi.mock(
  './auth/ImpersonationControl',
  () => ({
    default: () => (
      <div>Impersonation control</div>
    ),
  })
);

const mockedUseAuth =
  vi.mocked(useAuth);

const createAuthState = (
  overrides: Partial<AuthContextType> = {}
): AuthContextType => {
  return {
    user: null,
    tenant: null,
    impersonator: null,
    isLoading: false,
    isSuperadmin: false,
    isImpersonating: false,
    isAuthenticated: false,
    login: vi.fn(),
    register: vi.fn(),
    startImpersonation: vi.fn(),
    stopImpersonation: vi.fn(),
    logout: vi.fn(),
    refreshUser: vi.fn(),
    ...overrides,
  } as AuthContextType;
};

const renderProtectedRoute = () => {
  render(
    <MemoryRouter
      initialEntries={['/dashboard']}
    >
      <Routes>
        <Route
          path="/dashboard"
          element={
            <PrivateRoute>
              <div>Tenant dashboard</div>
            </PrivateRoute>
          }
        />

        <Route
          path="/login"
          element={<div>Login page</div>}
        />

        <Route
          path="/landlord"
          element={
            <div>Landlord platform</div>
          }
        />
      </Routes>
    </MemoryRouter>
  );
};

describe('PrivateRoute', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('redirects unauthenticated users to login', () => {
    mockedUseAuth.mockReturnValue(
      createAuthState()
    );

    renderProtectedRoute();

    expect(
      screen.getByText('Login page')
    ).toBeInTheDocument();
  });

  it('redirects a non-impersonating superadmin to the landlord platform', () => {
    mockedUseAuth.mockReturnValue(
      createAuthState({
        isAuthenticated: true,
        isSuperadmin: true,
      })
    );

    renderProtectedRoute();

    expect(
      screen.getByText(
        'Landlord platform'
      )
    ).toBeInTheDocument();

    expect(
      screen.queryByText(
        'Tenant dashboard'
      )
    ).not.toBeInTheDocument();
  });

  it('allows an ordinary tenant user into tenant routes', () => {
    mockedUseAuth.mockReturnValue(
      createAuthState({
        isAuthenticated: true,
      })
    );

    renderProtectedRoute();

    expect(
      screen.getByText(
        'Tenant dashboard'
      )
    ).toBeInTheDocument();
  });

  it('allows an impersonating superadmin into tenant routes', () => {
    mockedUseAuth.mockReturnValue(
      createAuthState({
        isAuthenticated: true,
        isSuperadmin: false,
        isImpersonating: true,
      })
    );

    renderProtectedRoute();

    expect(
      screen.getByText(
        'Tenant dashboard'
      )
    ).toBeInTheDocument();

    expect(
      screen.getByText(
        'Impersonation control'
      )
    ).toBeInTheDocument();
  });
});