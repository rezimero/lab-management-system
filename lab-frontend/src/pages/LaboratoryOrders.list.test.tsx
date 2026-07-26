import type { ReactNode } from 'react';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import api from '../api/client';
import LaboratoryOrders from './LaboratoryOrders';
import { orderPage, orderResource } from './laboratory-order-test/orderFixtures';

vi.mock('../api/client', () => ({ default: { get: vi.fn(), post: vi.fn() } }));
vi.mock('../contexts/AuthContext', () => ({
  useAuth: () => ({ user: { role: 'admin' } }),
}));
vi.mock('../components/Layout/Layout', () => ({
  default: ({ children, title }: { children: ReactNode; title: string }) => (
    <main><h1>{title}</h1>{children}</main>
  ),
}));

const getMock = vi.mocked(api.get);
const renderPage = () => render(
  <MemoryRouter initialEntries={['/laboratory-orders']}>
    <Routes>
      <Route path="/laboratory-orders" element={<LaboratoryOrders />} />
      <Route path="/laboratory-orders/new" element={<div>Create order route</div>} />
      <Route path="/laboratory-orders/:uuid" element={<div>Order detail route</div>} />
    </Routes>
  </MemoryRouter>
);

describe('LaboratoryOrders list', { timeout: 30_000 }, () => {
  beforeEach(() => {
    vi.clearAllMocks();
    getMock.mockResolvedValue({ data: orderPage } as never);
  });

  it('loads and renders tenant orders', async () => {
    renderPage();
    expect(await screen.findByText(orderResource.order_number)).toBeInTheDocument();
    expect(getMock).toHaveBeenCalledWith('/laboratory-orders', {
      params: { page: 1, per_page: 15 },
    });
    expect(screen.getByText('Ada Okafor')).toBeInTheDocument();
  });

  it('applies backend search, status and priority filters', async () => {
    const user = userEvent.setup();
    renderPage();
    await screen.findByText(orderResource.order_number);
    await user.type(screen.getByRole('textbox', { name: 'Search orders' }), 'Ada');
    await user.click(screen.getByRole('combobox', { name: 'Status' }));
    await user.click(screen.getByRole('option', { name: 'In progress' }));
    await user.click(screen.getByRole('combobox', { name: 'Priority' }));
    await user.click(screen.getByRole('option', { name: 'Urgent' }));
    await user.click(screen.getByRole('button', { name: 'Apply' }));
    await waitFor(() => {
      expect(getMock).toHaveBeenLastCalledWith('/laboratory-orders', {
        params: {
          page: 1,
          per_page: 15,
          search: 'Ada',
          status: 'in_progress',
          priority: 'urgent',
        },
      });
    });
  });

  it('opens the create order workspace', async () => {
    const user = userEvent.setup();
    renderPage();
    await user.click(await screen.findByRole('button', { name: 'Create order' }));
    expect(await screen.findByText('Create order route')).toBeInTheDocument();
  });

  it('opens the selected order', async () => {
    const user = userEvent.setup();
    renderPage();
    await user.click(await screen.findByText(orderResource.order_number));
    expect(await screen.findByText('Order detail route')).toBeInTheDocument();
  });
});
