import type {
  ReactNode,
} from 'react';

import {
  render,
  screen,
} from '@testing-library/react';

import {
  beforeEach,
  describe,
  expect,
  it,
  vi,
} from 'vitest';

import InventoryAccessRoute from './InventoryAccessRoute';

const accessState = vi.hoisted(() => ({
  role: 'admin',
  enabled: true,
  pending: false,
  failed: false,
}));

vi.mock('../contexts/AuthContext', () => ({
  useAuth: () => ({
    user: {
      role: accessState.role,
    },
  }),
}));

vi.mock('../features/commercial/hooks/useTenantEntitlements', () => ({
  useTenantEntitlements: () => ({
    data: {
      features: {
        inventory: {
          enabled: accessState.enabled,
          limit: null,
          name: 'Inventory and reagent management',
        },
      },
    },
    error: accessState.failed
      ? new Error('Entitlement lookup failed.')
      : null,
    isPending: accessState.pending,
    isError: accessState.failed,
  }),
}));

vi.mock('./Layout/Layout', () => ({
  default: ({
    children,
  }: {
    children: ReactNode;
  }) => <main>{children}</main>,
}));

vi.mock('./feedback/AppLoadingScreen', () => ({
  default: () => <div>Loading inventory access</div>,
}));

describe('InventoryAccessRoute', () => {
  beforeEach(() => {
    accessState.role = 'admin';
    accessState.enabled = true;
    accessState.pending = false;
    accessState.failed = false;
  });

  it('renders inventory for an entitled authorised role', () => {
    render(
      <InventoryAccessRoute>
        <div>Inventory workspace</div>
      </InventoryAccessRoute>
    );

    expect(screen.getByText('Inventory workspace')).toBeInTheDocument();
  });

  it('blocks roles without inventory permission', () => {
    accessState.role = 'receptionist';

    render(
      <InventoryAccessRoute>
        <div>Inventory workspace</div>
      </InventoryAccessRoute>
    );

    expect(screen.queryByText('Inventory workspace')).not.toBeInTheDocument();
    expect(screen.getByText(/role does not have access/i)).toBeInTheDocument();
  });

  it('blocks access when the subscription feature is disabled', () => {
    accessState.enabled = false;

    render(
      <InventoryAccessRoute>
        <div>Inventory workspace</div>
      </InventoryAccessRoute>
    );

    expect(screen.queryByText('Inventory workspace')).not.toBeInTheDocument();
    expect(screen.getByText(/not enabled for the current subscription/i)).toBeInTheDocument();
  });

  it('uses a controlled loading state while entitlements resolve', () => {
    accessState.pending = true;

    render(
      <InventoryAccessRoute>
        <div>Inventory workspace</div>
      </InventoryAccessRoute>
    );

    expect(screen.getByText('Loading inventory access')).toBeInTheDocument();
  });
});
