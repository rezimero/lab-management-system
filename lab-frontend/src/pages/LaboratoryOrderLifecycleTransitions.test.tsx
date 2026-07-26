import type { ReactNode } from 'react';
import { render, screen, waitFor, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import api from '../api/client';
import LaboratoryOrderView from './LaboratoryOrderView';
import {
  orderResource,
  orderResultPage,
  orderUuid,
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
let activeOrder = orderResource;

const renderPage = () => render(
  <MemoryRouter initialEntries={[`/laboratory-orders/${orderUuid}`]}>
    <Routes>
      <Route path="/laboratory-orders/:uuid" element={<LaboratoryOrderView />} />
    </Routes>
  </MemoryRouter>
);

const confirmAction = async (buttonName: string) => {
  const user = userEvent.setup();
  await user.click(await screen.findByRole('button', { name: buttonName }));
  const dialog = await screen.findByRole('dialog');
  await user.click(within(dialog).getByRole('button', { name: buttonName }));
};

describe('laboratory order lifecycle transitions', { timeout: 30_000 }, () => {
  beforeEach(() => {
    vi.clearAllMocks();
    activeOrder = orderResource;
    getMock.mockImplementation((url) => Promise.resolve({
      data: url === '/test-results'
        ? orderResultPage
        : { data: activeOrder },
    } as never));
    patchMock.mockResolvedValue({ data: { data: activeOrder } } as never);
  });

  it('begins processing after specimen collection', async () => {
    activeOrder = {
      ...sampleCollectedOrder,
      specimens: sampleCollectedOrder.specimens.map((specimen) => ({
        ...specimen,
        status: 'received',
        received_at: '2026-07-24T08:45:00.000Z',
      })),
    };
    renderPage();
    await confirmAction('Begin processing');
    await waitFor(() => {
      expect(patchMock).toHaveBeenCalledWith(
        `/laboratory-orders/${orderUuid}/status`,
        { status: 'in_progress' }
      );
      expect(getMock).toHaveBeenCalledTimes(4);
    });
  });

  it('cancels a pending order after confirmation', async () => {
    activeOrder = { ...orderResource, status: 'pending' };
    renderPage();
    await confirmAction('Cancel order');
    expect(patchMock).toHaveBeenCalledWith(
      `/laboratory-orders/${orderUuid}/status`,
      { status: 'cancelled' }
    );
  });

  it('surfaces backend completion eligibility errors', async () => {
    patchMock.mockRejectedValue({
      response: {
        data: {
          errors: {
            status: ['1 active order item(s) do not have approved results.'],
          },
        },
      },
    });
    renderPage();
    await confirmAction('Complete order');
    expect(await screen.findByText(
      '1 active order item(s) do not have approved results.'
    )).toBeInTheDocument();
  });
});
