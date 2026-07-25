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
import { sampleCollectedOrder } from './laboratory-order-test/specimenFixtures';

vi.mock('../api/client', () => ({
  default: { get: vi.fn(), post: vi.fn(), patch: vi.fn() },
}));
vi.mock('../contexts/AuthContext', () => ({
  useAuth: () => ({ user: { role: 'lab_technician' } }),
}));
vi.mock('../components/Layout/Layout', () => ({
  default: ({ children, title }: { children: ReactNode; title: string }) => (
    <main><h1>{title}</h1>{children}</main>
  ),
}));

const getMock = vi.mocked(api.get);
const patchMock = vi.mocked(api.patch);
const renderPage = () => render(
  <MemoryRouter initialEntries={[`/laboratory-orders/${orderUuid}`]}>
    <Routes>
      <Route path="/laboratory-orders/:uuid" element={<LaboratoryOrderView />} />
    </Routes>
  </MemoryRouter>
);

describe('laboratory order specimen status', { timeout: 30_000 }, () => {
  beforeEach(() => {
    vi.clearAllMocks();
    getMock.mockImplementation((url) => Promise.resolve({
      data: url === '/test-results'
        ? orderResultPage
        : { data: sampleCollectedOrder },
    } as never));
    patchMock.mockResolvedValue({
      data: { data: sampleCollectedOrder.specimens[0] },
    } as never);
  });

  it('receives a collected specimen and refreshes the order', async () => {
    const user = userEvent.setup();
    renderPage();
    await user.click(await screen.findByRole('button', { name: 'Receive' }));

    await waitFor(() => {
      expect(patchMock).toHaveBeenCalledWith(
        `/specimens/${specimenUuid}/status`,
        { status: 'received' }
      );
      expect(getMock).toHaveBeenCalledTimes(4);
    });
  });

  it('rejects a specimen with the required reason', async () => {
    const user = userEvent.setup();
    renderPage();
    await user.click(await screen.findByRole('button', { name: 'Reject' }));
    await user.type(await screen.findByLabelText(/Rejection reason/i), 'Incorrect container type');
    await user.click(screen.getByRole('button', { name: 'Reject specimen' }));

    await waitFor(() => {
      expect(patchMock).toHaveBeenCalledWith(
        `/specimens/${specimenUuid}/status`,
        {
          status: 'rejected',
          rejection_reason: 'Incorrect container type',
        }
      );
    });
  });
});
