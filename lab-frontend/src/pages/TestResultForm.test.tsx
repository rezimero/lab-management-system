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
import TestResultForm from './TestResultForm';

vi.mock('../api/client', () => ({
  default: {
    get: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
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

const patient = {
  id: 3,
  first_name: 'Ada',
  last_name: 'Lovelace',
  date_of_birth: '1990-01-02',
  gender: 'female',
  email: 'ada@example.test',
  phone: '08000000000',
  address: null,
  medical_record_number: 'MRN-001',
  notes: null,
  is_active: true,
  created_at: '2026-07-01T09:00:00.000Z',
  updated_at: '2026-07-01T09:00:00.000Z',
};

const labTest = {
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
};

const legacyResultData = [
  {
    name: 'Haemoglobin',
    value: '13.6',
    unit: 'g/dL',
    reference_range: '12.0-16.0',
  },
];

const getMock = vi.mocked(api.get);
const postMock = vi.mocked(api.post);
const putMock = vi.mocked(api.put);

const installGetResponses = ({
  result,
}: {
  result?: Record<string, unknown>;
} = {}) => {
  getMock.mockImplementation(async (url) => {
    if (url === '/patients') {
      return {
        data: {
          data: [patient],
        },
      } as never;
    }

    if (url === '/lab-tests') {
      return {
        data: {
          data: [labTest],
        },
      } as never;
    }

    if (String(url).startsWith('/test-results/')) {
      return {
        data: result,
      } as never;
    }

    throw new Error(`Unexpected GET ${String(url)}`);
  });
};

const renderForm = (path: string) => render(
  <MemoryRouter initialEntries={[path]}>
    <Routes>
      <Route
        path="/test-results/new"
        element={<TestResultForm />}
      />
      <Route
        path="/test-results/:id/edit"
        element={<TestResultForm />}
      />
      <Route
        path="/test-results"
        element={<div>Result list route</div>}
      />
    </Routes>
  </MemoryRouter>
);

describe('TestResultForm legacy characterization', { timeout: 30_000 }, () => {
  beforeEach(() => {
    vi.clearAllMocks();

    postMock.mockResolvedValue({
      data: {
        message: 'Created',
      },
    } as never);

    putMock.mockResolvedValue({
      data: {
        message: 'Updated',
      },
    } as never);
  });

  it('creates a standalone legacy result from patient and lab-test selections', async () => {
    installGetResponses();
    renderForm('/test-results/new');

    expect(
      await screen.findByRole('heading', {
        name: /add new test result/i,
      })
    ).toBeInTheDocument();

    const [patientSelect, labTestSelect] =
      await screen.findAllByRole(
        'combobox',
        {},
        {
          timeout: 15_000,
        }
      );

    expect(patientSelect).toHaveAccessibleName('');
    expect(labTestSelect).toHaveAccessibleName('');

    fireEvent.mouseDown(patientSelect);

    fireEvent.click(
      await screen.findByRole('option', {
        name: 'Ada Lovelace (MRN-001)',
      })
    );

    fireEvent.mouseDown(labTestSelect);

    fireEvent.click(
      await screen.findByRole('option', {
        name: 'Complete Blood Count',
      })
    );

    fireEvent.click(
      screen.getByRole('button', {
        name: /add result/i,
      })
    );

    fireEvent.change(
      screen.getByRole('textbox', {
        name: 'Parameter Name',
      }),
      {
        target: {
          value: 'Haemoglobin',
        },
      }
    );

    fireEvent.change(
      screen.getByRole('textbox', {
        name: 'Value',
      }),
      {
        target: {
          value: '13.6',
        },
      }
    );

    fireEvent.change(
      screen.getByRole('textbox', {
        name: 'Unit',
      }),
      {
        target: {
          value: 'g/dL',
        },
      }
    );

    fireEvent.change(
      screen.getByRole('textbox', {
        name: 'Reference Range',
      }),
      {
        target: {
          value: '12.0-16.0',
        },
      }
    );

    fireEvent.change(
      screen.getByRole('textbox', {
        name: 'Notes',
      }),
      {
        target: {
          value: 'Legacy result note',
        },
      }
    );

    fireEvent.click(
      screen.getByRole('button', {
        name: /create result/i,
      })
    );

    await waitFor(() => {
      expect(postMock).toHaveBeenCalledWith(
        '/test-results',
        expect.objectContaining({
          patient_id: 3,
          lab_test_id: 7,
          status: 'pending',
          results_data: [
            {
              name: 'Haemoglobin',
              value: '13.6',
              unit: 'g/dL',
              reference_range: '12.0-16.0',
            },
          ],
          notes: 'Legacy result note',
        })
      );
    });

    expect(
      await screen.findByText('Result list route')
    ).toBeInTheDocument();
  });
  it('loads string result data and updates the numeric legacy result route', async () => {
    const user = userEvent.setup();

    installGetResponses({
      result: {
        id: 27,
        patient_id: 3,
        lab_test_id: 7,
        user_id: 4,
        test_date: '2026-07-21',
        status: 'completed',
        results_data: JSON.stringify(legacyResultData),
        notes: 'Legacy result note',
        completed_at: '2026-07-21T10:30:00.000Z',
        created_at: '2026-07-21T09:00:00.000Z',
        updated_at: '2026-07-21T10:30:00.000Z',
      },
    });

    renderForm('/test-results/27/edit');

    expect(
      await screen.findByDisplayValue('Haemoglobin')
    ).toBeInTheDocument();

    expect(
      screen.getByDisplayValue('13.6')
    ).toBeInTheDocument();

    await user.click(
      screen.getByRole('button', {
        name: /update result/i,
      })
    );

    await waitFor(() => {
      expect(putMock).toHaveBeenCalledWith(
        '/test-results/27',
        expect.objectContaining({
          patient_id: 3,
          lab_test_id: 7,
          status: 'completed',
          results_data: legacyResultData,
        })
      );
    });

    expect(
      await screen.findByText('Result list route')
    ).toBeInTheDocument();
  });
});
