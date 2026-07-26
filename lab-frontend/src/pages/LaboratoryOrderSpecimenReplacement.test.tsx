import type { ReactNode } from 'react';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import api from '../api/client';
import LaboratoryOrderView from './LaboratoryOrderView';
import {
  orderResultPage,
  orderUuid,
  specimenUuid,
} from './laboratory-order-test/orderFixtures';
import { rejectedSpecimenOrder } from './laboratory-order-test/specimenFixtures';

const authState = vi.hoisted(() => ({ role: 'lab_technician' }));
vi.mock('../api/client', () => ({
  default: { get: vi.fn(), post: vi.fn(), patch: vi.fn() },
}));
vi.mock('../contexts/AuthContext', () => ({
  useAuth: () => ({ user: { role: authState.role } }),
}));
vi.mock('../components/Layout/Layout', () => ({
  default: ({ children, title }: { children: ReactNode; title: string }) => (
    <main><h1>{title}</h1>{children}</main>
  ),
}));

const getMock = vi.mocked(api.get);
const postMock = vi.mocked(api.post);
const renderPage = () => render(
  <MemoryRouter initialEntries={[`/laboratory-orders/${orderUuid}`]}>
    <Routes>
      <Route path="/laboratory-orders/:uuid" element={<LaboratoryOrderView />} />
    </Routes>
  </MemoryRouter>
);

describe('laboratory order specimen replacement', { timeout: 30_000 }, () => {
  beforeEach(() => {
    vi.clearAllMocks();
    authState.role = 'lab_technician';
    getMock.mockImplementation((url) => Promise.resolve({
      data: url === '/test-results'
        ? orderResultPage
        : { data: rejectedSpecimenOrder },
    } as never));
    postMock.mockResolvedValue({
      data: { data: rejectedSpecimenOrder.specimens[0] },
    } as never);
  });

  it('collects a replacement for a rejected specimen', async () => {
    const user = userEvent.setup();
    renderPage();
    await user.click(await screen.findByRole('button', { name: 'Replace' }));
    const container = screen.getByLabelText('Replacement container type');
    await user.clear(container);
    await user.type(container, 'Sodium Citrate Tube');
    await user.click(screen.getByRole('button', { name: 'Save replacement' }));

    await waitFor(() => {
      expect(postMock).toHaveBeenCalledWith(
        `/specimens/${specimenUuid}/replacement`,
        {
          specimen_type: 'Whole Blood',
          container_type: 'Sodium Citrate Tube',
          collection_site: 'Left Arm',
          volume: 5,
          volume_unit: 'mL',
        }
      );
      expect(getMock).toHaveBeenCalledTimes(4);
    });
  });

  it('hides specimen mutations from receptionists', async () => {
    authState.role = 'receptionist';
    renderPage();
    await screen.findByText('SP-20260724-A1B2C3D4');
    expect(screen.queryByRole('button', { name: 'Replace' })).not.toBeInTheDocument();
    expect(screen.queryByRole('button', { name: 'Receive' })).not.toBeInTheDocument();
    expect(screen.queryByRole('button', { name: 'Reject' })).not.toBeInTheDocument();
  });
});
