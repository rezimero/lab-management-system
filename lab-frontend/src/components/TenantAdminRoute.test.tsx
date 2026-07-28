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
  useAuth,
} from '../contexts/AuthContext';

import TenantAdminRoute from './TenantAdminRoute';

vi.mock(
  '../contexts/AuthContext',
  () => ({
    useAuth: vi.fn(),
  })
);

const mockedUseAuth =
  vi.mocked(useAuth);

const renderRoute = () => {
  render(
    <MemoryRouter
      initialEntries={[
        '/settings/laboratory-profile',
      ]}
    >
      <Routes>
        <Route
          path="/settings/laboratory-profile"
          element={
            <TenantAdminRoute>
              <div>Laboratory profile page</div>
            </TenantAdminRoute>
          }
        />

        <Route
          path="/dashboard"
          element={<div>Tenant dashboard</div>}
        />
      </Routes>
    </MemoryRouter>
  );
};

describe('TenantAdminRoute', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('allows tenant administrators', () => {
    mockedUseAuth.mockReturnValue({
      user: {
        role: 'admin',
      },
    } as ReturnType<typeof useAuth>);

    renderRoute();

    expect(
      screen.getByText(
        'Laboratory profile page'
      )
    ).toBeInTheDocument();
  });

  it('redirects non-administrators', () => {
    mockedUseAuth.mockReturnValue({
      user: {
        role: 'receptionist',
      },
    } as ReturnType<typeof useAuth>);

    renderRoute();

    expect(
      screen.getByText('Tenant dashboard')
    ).toBeInTheDocument();

    expect(
      screen.queryByText(
        'Laboratory profile page'
      )
    ).not.toBeInTheDocument();
  });
});