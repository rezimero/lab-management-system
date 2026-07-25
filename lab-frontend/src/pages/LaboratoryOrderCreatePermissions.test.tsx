import type { ReactNode } from 'react';
import { render, screen } from '@testing-library/react';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import api from '../api/client';
import LaboratoryOrderCreate from './LaboratoryOrderCreate';
import {
  labTestPage,
  patientPage,
} from './laboratory-order-test/orderCreationFixtures';

let role = 'receptionist';

vi.mock('../api/client', () => ({
  default: { get: vi.fn(), post: vi.fn() },
}));
vi.mock('../contexts/AuthContext', () => ({
  useAuth: () => ({ user: { role } }),
}));
vi.mock('../components/Layout/Layout', () => ({
  default: ({ children, title }: { children: ReactNode; title: string }) => (
    <main><h1>{title}</h1>{children}</main>
  ),
}));

const getMock = vi.mocked(api.get);
const renderPage = () => render(
  <MemoryRouter initialEntries={['/laboratory-orders/new']}>
    <Routes>
      <Route path="/laboratory-orders/new" element={<LaboratoryOrderCreate />} />
    </Routes>
  </MemoryRouter>
);

describe('laboratory order creation permissions', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    role = 'receptionist';
    getMock.mockImplementation((url) => Promise.resolve({
      data: url === '/patients' ? patientPage : labTestPage,
    } as never));
  });

  it('allows receptionists to create laboratory orders', async () => {
    renderPage();
    expect(await screen.findByRole('combobox', { name: /Patient/i })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Create order' })).toBeInTheDocument();
  }, 15000);

  it('blocks unknown tenant roles', async () => {
    role = 'viewer';
    renderPage();
    expect(await screen.findByText(
      'You do not have permission to create laboratory orders.'
    )).toBeInTheDocument();
    expect(screen.queryByRole('button', { name: 'Create order' })).not.toBeInTheDocument();
  });
});
