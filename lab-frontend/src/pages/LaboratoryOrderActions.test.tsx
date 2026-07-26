import type { ReactNode } from 'react';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import api from '../api/client';
import LaboratoryOrderView from './LaboratoryOrderView';
import {
  completedOrder,
  orderResource,
  orderResultPage,
  orderUuid,
  pendingItemUuid,
  reportUuid,
} from './laboratory-order-test/orderFixtures';

vi.mock('../api/client', () => ({
  default: { get: vi.fn(), post: vi.fn() },
}));
vi.mock('../contexts/AuthContext', () => ({
  useAuth: () => ({ user: { role: 'lab_technician' } }),
}));
vi.mock(
  '../features/laboratory-orders/permissions/orderPermissions',
  () => ({ roleAllowsOrderAction: () => true })
);
vi.mock('../components/Layout/Layout', () => ({
  default: ({
    children,
    title,
  }: {
    children: ReactNode;
    title: string;
  }) => <main><h1>{title}</h1>{children}</main>,
}));

const getMock = vi.mocked(api.get);
const postMock = vi.mocked(api.post);
const reportPath = `/laboratory-orders/${orderUuid}/report`;
const report = { uuid: reportUuid };

const renderPage = () => render(
  <MemoryRouter initialEntries={[`/laboratory-orders/${orderUuid}`]}>
    <Routes>
      <Route
        path="/laboratory-orders/:uuid"
        element={<LaboratoryOrderView />}
      />
      <Route
        path="/laboratory-reports/:id"
        element={<div>Report route</div>}
      />
    </Routes>
  </MemoryRouter>
);

const mockCompletedOrder = (exists: boolean) => {
  getMock.mockImplementation((url) => {
    if (url === '/test-results') {
      return Promise.resolve({ data: orderResultPage } as never);
    }
    if (url === reportPath) {
      return exists
        ? Promise.resolve({ data: { data: report } } as never)
        : Promise.reject({ response: { status: 404 } });
    }
    return Promise.resolve({ data: { data: completedOrder } } as never);
  });
};

describe('Laboratory order actions', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    getMock.mockImplementation((url) => Promise.resolve({
      data: url === '/test-results'
        ? orderResultPage
        : { data: orderResource },
    } as never));
    postMock.mockResolvedValue({
      data: { data: orderResource.items[0] },
    } as never);
  });

  it('starts a pending test item', async () => {
    const user = userEvent.setup();
    renderPage();

    await user.click(
      await screen.findByRole('button', { name: 'Start test' })
    );

    await waitFor(() => {
      expect(postMock).toHaveBeenCalledWith(
        `/laboratory-order-items/${pendingItemUuid}/start`
      );
      expect(getMock).toHaveBeenCalledTimes(4);
    });
  });

  it('shows only View report when issued', async () => {
    mockCompletedOrder(true);
    const user = userEvent.setup();
    renderPage();

    const viewButton = await screen.findByRole(
      'button',
      { name: 'View report' }
    );
    expect(
      screen.queryByRole('button', { name: 'Generate report' })
    ).not.toBeInTheDocument();

    await user.click(viewButton);

    expect(
      await screen.findByText('Report route')
    ).toBeInTheDocument();
    expect(getMock).toHaveBeenCalledWith(reportPath);
  });

  it('shows only Generate report when missing', async () => {
    mockCompletedOrder(false);
    postMock.mockResolvedValue({
      data: { data: report },
    } as never);

    const user = userEvent.setup();
    renderPage();

    const generateButton = await screen.findByRole(
      'button',
      { name: 'Generate report' }
    );
    expect(
      screen.queryByRole('button', { name: 'View report' })
    ).not.toBeInTheDocument();

    await user.click(generateButton);

    expect(
      await screen.findByText('Report route')
    ).toBeInTheDocument();
    expect(postMock).toHaveBeenCalledWith(reportPath);
  });
});