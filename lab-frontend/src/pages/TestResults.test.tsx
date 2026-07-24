import type {
  ReactNode,
} from 'react';

import {
  fireEvent,
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
import TestResults from './TestResults';

vi.mock('../api/client', () => ({
  default: {
    delete: vi.fn(),
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
  status: 'completed' as const,
  completed_at: '2026-07-21T10:30:00.000Z',
  created_at: '2026-07-21T09:00:00.000Z',
  updated_at: '2026-07-21T10:30:00.000Z',
  patient: {
    id: 3,
    first_name: 'Ada',
    last_name: 'Lovelace',
    date_of_birth: '1990-01-02',
    gender: 'female' as const,
    email: 'ada@example.test',
    phone: '08000000000',
    address: null,
    medical_record_number: 'MRN-001',
    notes: null,
    is_active: true,
    created_at: '2026-07-01T09:00:00.000Z',
    updated_at: '2026-07-01T09:00:00.000Z',
  },
  lab_test: {
    id: 7,
    name: 'Complete Blood Count',
    description: 'Blood cell analysis',
    category: 'Haematology',
    price: '5000.00',
    turnaround_time_hours: 4,
    parameters: [],
    is_active: true,
    created_at: '2026-07-01T09:00:00.000Z',
    updated_at: '2026-07-01T09:00:00.000Z',
  },
  user: {
    id: 4,
    name: 'Laboratory Technician',
    email: 'technician@example.test',
    role: 'lab_technician',
    is_active: true,
    created_at: '2026-07-01T09:00:00.000Z',
    updated_at: '2026-07-01T09:00:00.000Z',
  },
};

const getMock = vi.mocked(api.get);
const deleteMock = vi.mocked(api.delete);

const renderPage = () => render(
  <MemoryRouter initialEntries={['/test-results']}>
    <Routes>
      <Route
        path="/test-results"
        element={<TestResults />}
      />
      <Route
        path="/test-results/new"
        element={<div>New result route</div>}
      />
      <Route
        path="/test-results/:id"
        element={<div>Result view route</div>}
      />
      <Route
        path="/test-results/:id/edit"
        element={<div>Result edit route</div>}
      />
    </Routes>
  </MemoryRouter>
);

describe('TestResults legacy characterization', { timeout: 30_000 }, () => {
  beforeEach(() => {
    vi.clearAllMocks();

    getMock.mockResolvedValue({
      data: {
        data: [legacyResult],
        total: 1,
      },
    } as never);

    deleteMock.mockResolvedValue({
      data: {
        message: 'Deleted',
      },
    } as never);
  });

  it('requests and renders the legacy paginated result shape', async () => {
    renderPage();

    expect(
      await screen.findByText('Ada Lovelace')
    ).toBeInTheDocument();

    expect(
      screen.getByText('Complete Blood Count')
    ).toBeInTheDocument();

    expect(
      screen.getAllByText('Completed').length
    ).toBeGreaterThan(0);

    expect(getMock).toHaveBeenCalledWith(
      '/test-results',
      {
        params: {
          page: 1,
          per_page: 10,
          search: '',
          status: undefined,
        },
      }
    );
  });

  it('sends search text through the result-list query', async () => {
    renderPage();

    const search = await screen.findByPlaceholderText(
      'Search results...'
    );

    fireEvent.change(search, {
      target: {
        value: 'Ada',
      },
    });

    await waitFor(() => {
      expect(getMock).toHaveBeenLastCalledWith(
        '/test-results',
        {
          params: {
            page: 1,
            per_page: 10,
            search: 'Ada',
            status: undefined,
          },
        }
      );
    });
  });

  it('navigates to the standalone legacy creation route', async () => {
    const user = userEvent.setup();

    renderPage();

    await screen.findByText('Ada Lovelace');

    await user.click(
      screen.getByRole('button', {
        name: /add test result/i,
      })
    );

    expect(
      await screen.findByText('New result route')
    ).toBeInTheDocument();
  });

  it('navigates to the numeric legacy view and edit routes', async () => {
    const user = userEvent.setup();

    const firstRender = renderPage();

    await screen.findByText('Ada Lovelace');

    const viewButton = screen
      .getByTestId('VisibilityIcon')
      .closest('button');

    expect(viewButton).not.toBeNull();

    await user.click(viewButton!);

    expect(
      await screen.findByText('Result view route')
    ).toBeInTheDocument();

    firstRender.unmount();

    renderPage();

    await screen.findByText('Ada Lovelace');

    const editButton = screen
      .getByTestId('EditIcon')
      .closest('button');

    expect(editButton).not.toBeNull();

    await user.click(editButton!);

    expect(
      await screen.findByText('Result edit route')
    ).toBeInTheDocument();
  });

  it('sends the legacy delete request and refreshes the list', async () => {
    const user = userEvent.setup();

    renderPage();

    await screen.findByText('Ada Lovelace');

    const deleteButton = screen
      .getByTestId('DeleteIcon')
      .closest('button');

    expect(deleteButton).not.toBeNull();

    await user.click(deleteButton!);

    expect(
      screen.getByText(/action cannot be undone/i)
    ).toBeInTheDocument();

    await user.click(
      screen.getByRole('button', {
        name: /delete result/i,
      })
    );

    await waitFor(() => {
      expect(deleteMock).toHaveBeenCalledWith(
        '/test-results/27'
      );
    });

    await waitFor(() => {
      expect(getMock).toHaveBeenCalledTimes(2);
    });
  });
});
