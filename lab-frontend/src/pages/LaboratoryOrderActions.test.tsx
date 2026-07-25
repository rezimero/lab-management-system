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

vi.mock('../api/client', () => ({ default: { get: vi.fn(), post: vi.fn() } }));
vi.mock('../contexts/AuthContext', () => ({
  useAuth: () => ({ user: { role: 'lab_technician' } }),
}));
vi.mock('../components/Layout/Layout', () => ({
  default: ({ children, title }: { children: ReactNode; title: string }) => (
    <main><h1>{title}</h1>{children}</main>
  ),
}));

const getMock = vi.mocked(api.get);
const postMock = vi.mocked(api.post);
const report = { uuid: reportUuid };
const renderPage = () => render(
  <MemoryRouter initialEntries={[`/laboratory-orders/${orderUuid}`]}>
    <Routes>
      <Route path="/laboratory-orders/:uuid" element={<LaboratoryOrderView />} />
      <Route path="/laboratory-reports/:id" element={<div>Report route</div>} />
    </Routes>
  </MemoryRouter>
);

describe('Laboratory order actions', { timeout: 30_000 }, () => {
  beforeEach(() => {
    vi.clearAllMocks();
    getMock.mockImplementation((url) => Promise.resolve({
      data: url === '/test-results'
        ? orderResultPage
        : { data: orderResource },
    } as never));
    postMock.mockResolvedValue({ data: { data: orderResource.items[0] } } as never);
  });

  it('starts a pending test item and refreshes the order', async () => {
    const user = userEvent.setup();
    renderPage();
    await user.click(await screen.findByRole('button', { name: 'Start test' }));
    await waitFor(() => {
      expect(postMock).toHaveBeenCalledWith(
        `/laboratory-order-items/${pendingItemUuid}/start`
      );
      expect(getMock).toHaveBeenCalledTimes(4);
    });
  });

  it('opens an issued report for a completed order', async () => {
    getMock.mockImplementation((url) => Promise.resolve({
      data: url === '/test-results'
        ? orderResultPage
        : url.endsWith('/report')
          ? { data: report }
          : { data: completedOrder },
    } as never));
    const user = userEvent.setup();
    renderPage();
    await user.click(await screen.findByRole('button', { name: 'View report' }));
    expect(await screen.findByText('Report route')).toBeInTheDocument();
    expect(getMock).toHaveBeenLastCalledWith(
      `/laboratory-orders/${orderUuid}/report`
    );
  });

  it('generates a report for a completed order', async () => {
    getMock.mockImplementation((url) => Promise.resolve({
      data: url === '/test-results'
        ? orderResultPage
        : { data: completedOrder },
    } as never));
    postMock.mockResolvedValue({ data: { data: report } } as never);
    const user = userEvent.setup();
    renderPage();
    await user.click(await screen.findByRole('button', { name: 'Generate report' }));
    expect(await screen.findByText('Report route')).toBeInTheDocument();
    expect(postMock).toHaveBeenCalledWith(
      `/laboratory-orders/${orderUuid}/report`
    );
  });
});
