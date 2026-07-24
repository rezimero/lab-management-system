import type {
  ReactNode,
} from 'react';

import {
  render,
  screen,
  waitFor,
} from '@testing-library/react';

import userEvent from '@testing-library/user-event';

import {
  MemoryRouter,
  Route,
  Routes,
} from 'react-router-dom';

import {
  beforeEach,
  describe,
  expect,
  it,
  vi,
} from 'vitest';

import api from '../api/client';
import TestResultView from './TestResultView';

vi.mock('../api/client', () => ({
  default: {
    get: vi.fn(),
  },
}));

vi.mock('../components/Layout/Layout', () => ({
  default: ({
    children,
    title,
  }: {
    children: ReactNode;
    title: string;
  }) => (
    <main>
      <h1>{title}</h1>
      {children}
    </main>
  ),
}));

const legacyResult = {
  id: 27,
  patient_id: 3,
  lab_test_id: 7,
  user_id: 4,
  test_date: '2026-07-21',
  results_data: [
    {
      name: 'Haemoglobin',
      value: '13.6',
      unit: 'g/dL',
      reference_range: '12.0-16.0',
    },
  ],
  notes: 'Legacy result note',
  status: 'completed',
  completed_at: '2026-07-21T10:30:00.000Z',
  created_at: '2026-07-21T09:00:00.000Z',
  updated_at: '2026-07-21T10:30:00.000Z',
  patient: {
    id: 3,
    first_name: 'Ada',
    last_name: 'Lovelace',
    email: 'ada@example.test',
    phone: '08000000000',
    date_of_birth: '1990-01-02',
    gender: 'female',
    medical_record_number: 'MRN-001',
    address: null,
  },
  lab_test: {
    id: 7,
    name: 'Complete Blood Count',
    description: 'Blood cell analysis',
    category: 'Haematology',
    price: '5000.00',
    turnaround_time_hours: 4,
    parameters: [],
  },
  user: {
    id: 4,
    name: 'Laboratory Technician',
    email: 'technician@example.test',
  },
};

const getMock = vi.mocked(api.get);

const renderPage = () => render(
  <MemoryRouter initialEntries={['/test-results/27']}>
    <Routes>
      <Route
        path="/test-results/:id"
        element={<TestResultView />}
      />
      <Route
        path="/test-results"
        element={<div>Result list route</div>}
      />
    </Routes>
  </MemoryRouter>
);

describe('TestResultView legacy characterization', { timeout: 30_000 }, () => {
  beforeEach(() => {
    vi.clearAllMocks();

    getMock.mockResolvedValue({
      data: legacyResult,
    } as never);
  });

  it('renders the numeric legacy result as a printable report surface', async () => {
    renderPage();

    expect(
      await screen.findByText('Test Result Report')
    ).toBeInTheDocument();

    expect(getMock).toHaveBeenCalledWith(
      '/test-results/27'
    );

    expect(
      screen.getByText(/Report ID: #000027/)
    ).toBeInTheDocument();

    expect(
      screen.getByText('Ada Lovelace')
    ).toBeInTheDocument();

    expect(
      screen.getByText('Complete Blood Count')
    ).toBeInTheDocument();

    expect(
      screen.getByText('Haemoglobin')
    ).toBeInTheDocument();

    expect(
      screen.getByText('Legacy result note')
    ).toBeInTheDocument();

    expect(
      screen.getByText(/Performed by: Laboratory Technician/)
    ).toBeInTheDocument();

    expect(
      screen.getByText('Laboratory Report')
    ).toBeInTheDocument();
  });

  it('uses browser printing for both print and PDF actions', async () => {
    const user = userEvent.setup();
    const printMock = vi.fn();

    Object.defineProperty(window, 'print', {
      configurable: true,
      value: printMock,
    });

    renderPage();

    await screen.findByText('Test Result Report');

    await user.click(
      screen.getByRole('button', {
        name: 'Print',
      })
    );

    await user.click(
      screen.getByRole('button', {
        name: 'Download PDF',
      })
    );

    expect(printMock).toHaveBeenCalledTimes(2);
  });

  it('surfaces the API error when the result cannot be loaded', async () => {
    getMock.mockRejectedValueOnce({
      response: {
        data: {
          message: 'Result not found',
        },
      },
    });

    renderPage();

    expect(
      await screen.findByText('Result not found')
    ).toBeInTheDocument();

    await waitFor(() => {
      expect(getMock).toHaveBeenCalledWith(
        '/test-results/27'
      );
    });
  });
});
