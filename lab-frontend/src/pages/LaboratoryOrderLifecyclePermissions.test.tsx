import type { ReactNode } from 'react';
import { render, screen } from '@testing-library/react';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import api from '../api/client';
import LaboratoryOrderView from './LaboratoryOrderView';
import {
  orderResource,
  orderResultPage,
  orderUuid,
} from './laboratory-order-test/orderFixtures';

vi.mock('../api/client', () => ({
  default: { get: vi.fn(), post: vi.fn(), patch: vi.fn() },
}));
vi.mock('../contexts/AuthContext', () => ({
  useAuth: () => ({ user: { role: 'receptionist' } }),
}));
vi.mock('../components/Layout/Layout', () => ({
  default: ({ children, title }: { children: ReactNode; title: string }) => (
    <main><h1>{title}</h1>{children}</main>
  ),
}));

const getMock = vi.mocked(api.get);

describe('laboratory order lifecycle permissions', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    getMock.mockImplementation((url) => Promise.resolve({
      data: url === '/test-results'
        ? orderResultPage
        : { data: orderResource },
    } as never));
  });

  it('hides lifecycle mutations from receptionists', async () => {
    render(
      <MemoryRouter initialEntries={[`/laboratory-orders/${orderUuid}`]}>
        <Routes>
          <Route path="/laboratory-orders/:uuid" element={<LaboratoryOrderView />} />
        </Routes>
      </MemoryRouter>
    );

    expect(await screen.findByText(orderResource.order_number)).toBeInTheDocument();
    expect(screen.queryByRole('button', { name: 'Complete order' })).not.toBeInTheDocument();
    expect(screen.queryByText('Order lifecycle')).not.toBeInTheDocument();
  });
});
