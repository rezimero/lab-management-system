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
} from './laboratory-order-test/orderFixtures';
import { pendingSpecimenOrder } from './laboratory-order-test/specimenFixtures';

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
const postMock = vi.mocked(api.post);
const renderPage = () => render(
  <MemoryRouter initialEntries={[`/laboratory-orders/${orderUuid}`]}>
    <Routes>
      <Route path="/laboratory-orders/:uuid" element={<LaboratoryOrderView />} />
    </Routes>
  </MemoryRouter>
);

describe('laboratory order specimen collection', { timeout: 30_000 }, () => {
  beforeEach(() => {
    vi.clearAllMocks();
    getMock.mockImplementation((url) => Promise.resolve({
      data: url === '/test-results'
        ? orderResultPage
        : { data: pendingSpecimenOrder },
    } as never));
    postMock.mockResolvedValue({ data: { data: pendingSpecimenOrder } } as never);
  });

  it('collects a specimen covering every active order item', async () => {
    const user = userEvent.setup();
    renderPage();
    await user.click(await screen.findByRole('button', { name: 'Collect specimens' }));
    await user.type(await screen.findByLabelText(/Specimen type 1/i), 'Whole Blood');
    await user.type(await screen.findByLabelText('Container type 1'), 'EDTA Tube');
    await user.click(screen.getByRole('button', { name: 'Save specimens' }));

    await waitFor(() => {
      expect(postMock).toHaveBeenCalledWith(
        `/laboratory-orders/${orderUuid}/specimens`,
        {
          specimens: [
            {
              specimen_type: 'Whole Blood',
              container_type: 'EDTA Tube',
              order_item_uuids: pendingSpecimenOrder.items.map((item) => item.uuid),
            },
          ],
        }
      );
      expect(getMock).toHaveBeenCalledTimes(4);
    });
  });

  it('requires coverage for every active order item', async () => {
    const user = userEvent.setup();
    renderPage();
    await user.click(await screen.findByRole('button', { name: 'Collect specimens' }));
    await user.type(await screen.findByLabelText(/Specimen type 1/i), 'Whole Blood');
    await user.click(screen.getByLabelText('Malaria Parasite'));
    await user.click(screen.getByRole('button', { name: 'Save specimens' }));

    expect(await screen.findByText(
      'Every active order item must be assigned to a specimen.'
    )).toBeInTheDocument();
    expect(postMock).not.toHaveBeenCalled();
  });
});
