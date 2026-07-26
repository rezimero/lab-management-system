import type {
  ReactNode,
} from 'react';
import {
  screen,
  waitFor,
} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {
  beforeEach,
  describe,
  expect,
  it,
  vi,
} from 'vitest';
import api from '../api/client';
import {
  renderResultView,
} from './test-result-view/TestResultViewHarness';
import {
  resultResource,
  resultUuid,
  resultWithStatus,
} from './test-result-view/testResultViewFixtures';
const authState = vi.hoisted(() => ({
  role: 'admin',
}));
vi.mock('../api/client', () => ({
  default: {
    get: vi.fn(),
    post: vi.fn(),
  },
}));
vi.mock('../contexts/AuthContext', () => ({
  useAuth: () => ({
    user: {
      role: authState.role,
    },
  }),
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
const getMock = vi.mocked(api.get);
describe(
  'TestResultView display',
  {
    timeout: 30_000,
  },
  () => {
    beforeEach(() => {
      vi.clearAllMocks();
      authState.role = 'admin';
      getMock.mockResolvedValue({
        data: {
          data: resultResource,
        },
      } as never);
    });
    it(
      'renders the UUID resource',
      async () => {
        renderResultView();
        expect(
          await screen.findByText(
            'Test Result Details'
          )
        ).toBeInTheDocument();
        expect(getMock)
          .toHaveBeenCalledWith(
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
          screen.getByText('Haemoglobin')
        ).toBeInTheDocument();
        expect(
          screen.getByText('High')
        ).toBeInTheDocument();
        expect(
          screen.getByText(
            'Laboratory Reviewer'
          )
        ).toBeInTheDocument();
      }
    );
    it(
      'shows a rejection reason',
      async () => {
        getMock.mockResolvedValueOnce({
          data: {
            data: resultWithStatus(
              'rejected'
            ),
          },
        } as never);
        renderResultView();
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
      }
    );
    it(
      'prints without a false PDF action',
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
        renderResultView();
        await user.click(
          await screen.findByRole(
            'button',
            {
              name: 'Print result',
            }
          )
        );
        expect(printMock)
          .toHaveBeenCalledTimes(1);
        expect(
          screen.queryByRole(
            'button',
            {
              name: /download pdf/i,
            }
          )
        ).not.toBeInTheDocument();
      }
    );
    it(
      'surfaces a loading error',
      async () => {
        getMock.mockRejectedValueOnce({
          response: {
            data: {
              message:
                'Result not found',
            },
          },
        });
        renderResultView();
        expect(
          await screen.findByText(
            'Result not found'
          )
        ).toBeInTheDocument();
        await waitFor(() => {
          expect(getMock)
            .toHaveBeenCalledTimes(1);
        });
      }
    );
  }
);
