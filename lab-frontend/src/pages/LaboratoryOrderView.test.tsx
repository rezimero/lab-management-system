import type { ReactNode } from 'react';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import api from '../api/client';
import LaboratoryOrderView from './LaboratoryOrderView';
import {
  orderResource,
  orderResultPage,
  orderUuid,
  resultUuid,
} from './laboratory-order-test/orderFixtures';

const authState = vi.hoisted(() => ({ role: 'admin' }));
vi.mock('../api/client', () => ({ default: { get: vi.fn(), post: vi.fn() } }));
vi.mock('../contexts/AuthContext', () => ({
  useAuth: () => ({ user: { role: authState.role } }),
}));
vi.mock('../components/Layout/Layout', () => ({
  default: ({ children, title }: { children: ReactNode; title: string }) => (
    <main><h1>{title}</h1>{children}</main>
  ),
}));

const getMock = vi.mocked(api.get);
const renderPage = () => render(
  <MemoryRouter initialEntries={[`/laboratory-orders/${orderUuid}`]}>
    <Routes>
      <Route path="/laboratory-orders/:uuid" element={<LaboratoryOrderView />} />
      <Route path="/test-results/:id" element={<div>Result detail route</div>} />
      <Route path="/test-results/new" element={<div>Result form route</div>} />
    </Routes>
  </MemoryRouter>
);

describe('LaboratoryOrderView', { timeout: 30_000 }, () => {
  beforeEach(() => {
    vi.clearAllMocks();
    authState.role = 'admin';
    getMock.mockImplementation((url) => Promise.resolve({
      data: url === '/test-results'
        ? orderResultPage
        : { data: orderResource },
    } as never));
  });

  it('renders the order, items and specimens', async () => {
    renderPage();
    expect(await screen.findByText(orderResource.order_number)).toBeInTheDocument();
    expect(screen.getByText('Ada Okafor')).toBeInTheDocument();
    expect(screen.getByText('Full Blood Count')).toBeInTheDocument();
    expect(screen.getByText('SP-20260724-A1B2C3D4')).toBeInTheDocument();
    expect(getMock).toHaveBeenCalledWith(`/laboratory-orders/${orderUuid}`);
    expect(getMock).toHaveBeenCalledWith('/test-results', {
      params: { order_uuid: orderUuid, page: 1, per_page: 100 },
    });
  });

  it('opens an existing test result', async () => {
    const user = userEvent.setup();
    renderPage();
    await user.click(await screen.findByRole('button', { name: 'Open result' }));
    expect(await screen.findByText('Result detail route')).toBeInTheDocument();
    expect(resultUuid).toBeTruthy();
  });

  it('hides result creation actions from receptionists', async () => {
    authState.role = 'receptionist';
    renderPage();
    await screen.findByText(orderResource.order_number);
    expect(screen.queryByRole('button', { name: 'Start test' })).not.toBeInTheDocument();
  });
});
