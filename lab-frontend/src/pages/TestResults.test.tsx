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

const resourceResult = {
  uuid: 'result-uuid-27',
  status: 'approved' as const,
  results_data: [
    {
      name: 'Haemoglobin',
      value: '13.6',
      unit: 'g/dL',
      reference_range: '12.0-16.0',
      flag: null,
      notes: null,
    },
  ],
  notes: 'Approved result note',
  rejection_reason: null,
  order_item: {
    uuid: 'order-item-uuid-7',
    test_name: 'Complete Blood Count',
    category: 'Haematology',
    status: 'completed',
    parameters: [],
    order: {
      uuid: 'order-uuid-42',
      order_number: 'LAB-2026-0042',
      priority: 'routine',
      status: 'completed',
      patient: {
        id: 3,
        medical_record_number: 'MRN-001',
        first_name: 'Ada',
        last_name: 'Lovelace',
        full_name: 'Ada Lovelace',
      },
    },
  },
  entered_by: {
    id: 4,
    name: 'Laboratory Technician',
    email: 'technician@example.test',
  },
  submitted_by: {
    id: 4,
    name: 'Laboratory Technician',
    email: 'technician@example.test',
  },
  reviewed_by: {
    id: 5,
    name: 'Laboratory Reviewer',
    email: 'reviewer@example.test',
  },
  submitted_at: '2026-07-21T10:00:00.000Z',
  reviewed_at: '2026-07-21T10:30:00.000Z',
  created_at: '2026-07-21T09:00:00.000Z',
  updated_at: '2026-07-21T10:30:00.000Z',
};

const getMock = vi.mocked(api.get);

const renderPage = () => render(
  <MemoryRouter initialEntries={['/test-results']}>
    <Routes>
      <Route
        path="/test-results"
        element={<TestResults />}
      />
      <Route
        path="/test-results/:uuid"
        element={<div>Result resource route</div>}
      />
    </Routes>
  </MemoryRouter>
);

const paginatedResponse = {
  data: {
    data: [resourceResult],
    links: {
      first: null,
      last: null,
      prev: null,
      next: null,
    },
    meta: {
      current_page: 1,
      from: 1,
      last_page: 5,
      path: '/api/test-results',
      per_page: 10,
      to: 10,
      total: 42,
    },
  },
};

describe('TestResults resource integration', { timeout: 30_000 }, () => {
  beforeEach(() => {
    vi.clearAllMocks();
    getMock.mockResolvedValue(paginatedResponse as never);
  });

  it('requests and renders the backend resource collection', async () => {
    renderPage();

    expect(
      await screen.findByText('Ada Lovelace')
    ).toBeInTheDocument();

    expect(
      screen.getByText('MRN-001')
    ).toBeInTheDocument();

    expect(
      screen.getByText('Complete Blood Count')
    ).toBeInTheDocument();

    expect(
      screen.getByText('Haematology')
    ).toBeInTheDocument();

    expect(
      screen.getByText('LAB-2026-0042')
    ).toBeInTheDocument();

    expect(
      screen.getByText('Approved')
    ).toBeInTheDocument();

    expect(
      screen.getByText(/42 results total/i)
    ).toBeInTheDocument();

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

  it('sends search text through the result query', async () => {
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

  it('sends backend workflow statuses through the result query', async () => {
    const user = userEvent.setup();

    renderPage();

    await screen.findByText('Ada Lovelace');

    await user.click(
      screen.getByRole('combobox', {
        name: 'Status',
      })
    );

    await user.click(
      await screen.findByRole('option', {
        name: 'Submitted',
      })
    );

    await waitFor(() => {
      expect(getMock).toHaveBeenLastCalledWith(
        '/test-results',
        {
          params: {
            page: 1,
            per_page: 10,
            search: '',
            status: 'submitted',
          },
        }
      );
    });
  });

  it('navigates to the UUID result route', async () => {
    const user = userEvent.setup();

    renderPage();

    await screen.findByText('Ada Lovelace');

    await user.click(
      screen.getByRole('button', {
        name: 'View Complete Blood Count',
      })
    );

    expect(
      await screen.findByText('Result resource route')
    ).toBeInTheDocument();
  });

  it('does not expose unsupported standalone mutations', async () => {
    renderPage();

    await screen.findByText('Ada Lovelace');

    expect(
      screen.getByText(
        /standalone creation and deletion are not available/i
      )
    ).toBeInTheDocument();

    expect(
      screen.queryByRole('button', {
        name: /add test result/i,
      })
    ).not.toBeInTheDocument();

    expect(
      screen.queryByTestId('EditIcon')
    ).not.toBeInTheDocument();

    expect(
      screen.queryByTestId('DeleteIcon')
    ).not.toBeInTheDocument();
  });
});
