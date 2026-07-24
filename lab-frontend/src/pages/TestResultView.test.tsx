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

const resultUuid =
  'f3e64296-9fc3-4bb5-9702-a6c72e7f10c8';

const resourceResult = {
  uuid: resultUuid,
  status: 'approved' as const,

  results_data: [
    {
      name: 'Haemoglobin',
      value: '17.2',
      unit: 'g/dL',
      reference_range: '12.0-16.0',
      flag: 'high',
      notes: 'Confirm with repeat sample',
    },
  ],

  notes: 'Clinical correlation is advised.',
  rejection_reason: null,

  order_item: {
    uuid:
      '19c2924f-6364-457a-a0e4-b9f9ca8e67f2',
    test_name: 'Complete Blood Count',
    category: 'Haematology',
    status: 'completed',
    parameters: [],

    order: {
      uuid:
        '7c18bbcb-ab40-45b8-8118-932313dc18ff',
      order_number: 'LAB-2026-0042',
      priority: 'urgent',
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

  submitted_at:
    '2026-07-21T10:00:00.000Z',

  reviewed_at:
    '2026-07-21T10:30:00.000Z',

  created_at:
    '2026-07-21T09:00:00.000Z',

  updated_at:
    '2026-07-21T10:30:00.000Z',
};

const getMock = vi.mocked(api.get);

const renderPage = () => render(
  <MemoryRouter
    initialEntries={[
      `/test-results/${resultUuid}`,
    ]}
  >
    <Routes>
      <Route
        path="/test-results/:id"
        element={<TestResultView />}
      />

      <Route
        path="/test-results"
        element={
          <div>Result list route</div>
        }
      />
    </Routes>
  </MemoryRouter>
);

describe(
  'TestResultView resource integration',
  {
    timeout: 30_000,
  },
  () => {
    beforeEach(() => {
      vi.clearAllMocks();

      getMock.mockResolvedValue({
        data: {
          data: resourceResult,
        },
      } as never);
    });

    it(
      'loads and renders the UUID backend resource',
      async () => {
        renderPage();

        expect(
          await screen.findByText(
            'Test Result Details'
          )
        ).toBeInTheDocument();

        expect(
          getMock
        ).toHaveBeenCalledWith(
          `/test-results/${resultUuid}`
        );

        expect(
          screen.getByText(
            `Result UUID: ${resultUuid}`
          )
        ).toBeInTheDocument();

        expect(
          screen.getByText('Approved')
        ).toBeInTheDocument();

        expect(
          screen.getByText('Ada Lovelace')
        ).toBeInTheDocument();

        expect(
          screen.getByText('MRN-001')
        ).toBeInTheDocument();

        expect(
          screen.getByText(
            'Complete Blood Count'
          )
        ).toBeInTheDocument();

        expect(
          screen.getByText('Haematology')
        ).toBeInTheDocument();

        expect(
          screen.getByText('LAB-2026-0042')
        ).toBeInTheDocument();

        expect(
          screen.getByText('Haemoglobin')
        ).toBeInTheDocument();

        expect(
          screen.getByText('High')
        ).toBeInTheDocument();

        expect(
          screen.getByText(
            'Confirm with repeat sample'
          )
        ).toBeInTheDocument();

        expect(
          screen.getByText(
            'Clinical correlation is advised.'
          )
        ).toBeInTheDocument();

        expect(
          screen.getAllByText(
            'Laboratory Technician'
          ).length
        ).toBeGreaterThan(0);

        expect(
          screen.getByText(
            'Laboratory Reviewer'
          )
        ).toBeInTheDocument();
      }
    );

    it(
      'shows the backend rejection reason',
      async () => {
        getMock.mockResolvedValueOnce({
          data: {
            data: {
              ...resourceResult,
              status: 'rejected',
              rejection_reason:
                'Submitted values require correction.',
            },
          },
        } as never);

        renderPage();

        expect(
          await screen.findByText(
            'Submitted values require correction.'
          )
        ).toBeInTheDocument();

        expect(
          screen.getByText(
            'Rejection reason'
          )
        ).toBeInTheDocument();

        expect(
          screen.getByText('Rejected')
        ).toBeInTheDocument();
      }
    );

    it(
      'prints the result without presenting a false PDF action',
      async () => {
        const user = userEvent.setup();
        const printMock = vi.fn();

        Object.defineProperty(
          window,
          'print',
          {
            configurable: true,
            value: printMock,
          }
        );

        renderPage();

        await screen.findByText(
          'Test Result Details'
        );

        await user.click(
          screen.getByRole(
            'button',
            {
              name: 'Print result',
            }
          )
        );

        expect(
          printMock
        ).toHaveBeenCalledTimes(1);

        expect(
          screen.queryByRole(
            'button',
            {
              name: /download pdf/i,
            }
          )
        ).not.toBeInTheDocument();

        expect(
          screen.queryByText(
            'Laboratory Report'
          )
        ).not.toBeInTheDocument();
      }
    );

    it(
      'surfaces the API error when the UUID resource cannot be loaded',
      async () => {
        getMock.mockRejectedValueOnce({
          response: {
            data: {
              message: 'Result not found',
            },
          },
        });

        renderPage();

        expect(
          await screen.findByText(
            'Result not found'
          )
        ).toBeInTheDocument();

        await waitFor(() => {
          expect(
            getMock
          ).toHaveBeenCalledWith(
            `/test-results/${resultUuid}`
          );
        });
      }
    );
  }
);
