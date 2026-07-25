import type { ReactNode } from 'react';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import api from '../api/client';
import LaboratoryOrderCreate from './LaboratoryOrderCreate';
import {
  bloodCountTest,
  createdOrderResponse,
  labTestPage,
  orderPatient,
  patientPage,
} from './laboratory-order-test/orderCreationFixtures';

vi.mock('../api/client', () => ({
  default: { get: vi.fn(), post: vi.fn() },
}));
vi.mock('../contexts/AuthContext', () => ({
  useAuth: () => ({ user: { role: 'admin' } }),
}));
vi.mock('../components/Layout/Layout', () => ({
  default: ({ children, title }: { children: ReactNode; title: string }) => (
    <main><h1>{title}</h1>{children}</main>
  ),
}));

const getMock = vi.mocked(api.get);
const postMock = vi.mocked(api.post);

const renderPage = () => render(
  <MemoryRouter initialEntries={['/laboratory-orders/new']}>
    <Routes>
      <Route path="/laboratory-orders/new" element={<LaboratoryOrderCreate />} />
      <Route path="/laboratory-orders/:uuid" element={<div>Created order detail</div>} />
    </Routes>
  </MemoryRouter>
);

const choosePatientAndTest = async () => {
  renderPage();
  const user = userEvent.setup();
  const patientInput = await screen.findByRole('combobox', { name: /Patient/i });
  await user.click(patientInput);
  await user.click(await screen.findByRole('option', { name: /Ada Okafor/i }));
  const testInput = screen.getByRole('combobox', { name: /Laboratory tests/i });
  await user.click(testInput);
  await user.click(await screen.findByRole('option', { name: /Full Blood Count/i }));
  return user;
};

describe('laboratory order creation', { timeout: 30_000 }, () => {
  beforeEach(() => {
    vi.clearAllMocks();
    getMock.mockImplementation((url) => Promise.resolve({
      data: url === '/patients' ? patientPage : labTestPage,
    } as never));
    postMock.mockResolvedValue({ data: createdOrderResponse } as never);
  });

  it('creates an order from active patient and test selections', async () => {
    const user = await choosePatientAndTest();
    await user.click(screen.getByRole('combobox', { name: 'Priority' }));
    await user.click(screen.getByRole('option', { name: 'Urgent' }));
    await user.type(screen.getByRole('textbox', { name: 'Referring clinician' }), 'Dr Musa Bello');
    await user.type(screen.getByRole('textbox', { name: 'Clinical notes' }), 'Persistent fatigue');
    await user.click(screen.getByRole('button', { name: 'Create order' }));

    await waitFor(() => expect(postMock).toHaveBeenCalledWith(
      '/laboratory-orders',
      {
        patient_id: orderPatient.id,
        priority: 'urgent',
        referring_clinician: 'Dr Musa Bello',
        clinical_notes: 'Persistent fatigue',
        items: [{ lab_test_id: bloodCountTest.id }],
      }
    ));
    expect(await screen.findByText('Created order detail')).toBeInTheDocument();
  });

  it('requires at least one active laboratory test', async () => {
    const user = userEvent.setup();
    renderPage();
    const patientInput = await screen.findByRole('combobox', { name: /Patient/i });
    await user.click(patientInput);
    await user.click(await screen.findByRole('option', { name: /Ada Okafor/i }));
    await user.click(screen.getByRole('button', { name: 'Create order' }));
    expect(await screen.findByText('Select at least one active laboratory test.')).toBeInTheDocument();
    expect(postMock).not.toHaveBeenCalled();
  });

  it('surfaces backend catalogue validation errors', async () => {
    postMock.mockRejectedValueOnce({
      response: {
        data: {
          errors: {
            'items.0.lab_test_id': [
              'The selected laboratory test is unavailable or inactive.',
            ],
          },
        },
      },
    });
    const user = await choosePatientAndTest();
    await user.click(screen.getByRole('button', { name: 'Create order' }));
    expect(await screen.findByText(
      'The selected laboratory test is unavailable or inactive.'
    )).toBeInTheDocument();
  });
});
