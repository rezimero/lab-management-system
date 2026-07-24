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
  resultUuid,
  resultWithStatus,
} from './test-result-view/testResultViewFixtures';
const authState = vi.hoisted(() => ({
  role: 'doctor',
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
const postMock = vi.mocked(api.post);
describe(
  'TestResultView review workflow',
  {
    timeout: 30_000,
  },
  () => {
    beforeEach(() => {
      vi.clearAllMocks();
      authState.role = 'doctor';
      getMock.mockResolvedValue({
        data: {
          data: resultWithStatus(
            'submitted'
          ),
        },
      } as never);
    });
    it(
      'approves a submitted result',
      async () => {
        const user = userEvent.setup();
        postMock.mockResolvedValue({
          data: {
            data: resultWithStatus(
              'approved'
            ),
          },
        } as never);
        renderResultView();
        await user.click(
          await screen.findByRole(
            'button',
            {
              name: 'Approve result',
            }
          )
        );
        await waitFor(() => {
          expect(postMock)
            .toHaveBeenCalledWith(
              `/test-results/${resultUuid}/approve`
            );
        });
        expect(
          await screen.findByText(
            'Approved'
          )
        ).toBeInTheDocument();
      }
    );
    it(
      'rejects with the required reason',
      async () => {
        const user = userEvent.setup();
        postMock.mockResolvedValue({
          data: {
            data: resultWithStatus(
              'rejected'
            ),
          },
        } as never);
        renderResultView();
        await user.click(
          await screen.findByRole(
            'button',
            {
              name: 'Reject result',
            }
          )
        );
        await user.type(
          screen.getByRole(
            'textbox',
            {
              name: 'Rejection reason',
            }
          ),
          'Repeat the measurement.'
        );
        await user.click(
          screen.getByRole(
            'button',
            {
              name: 'Reject result',
            }
          )
        );
        await waitFor(() => {
          expect(postMock)
            .toHaveBeenCalledWith(
              `/test-results/${resultUuid}/reject`,
              {
                rejection_reason:
                  'Repeat the measurement.',
              }
            );
        });
        expect(
          await screen.findByText(
            'Rejected'
          )
        ).toBeInTheDocument();
      }
    );
    it(
      'surfaces a workflow API error',
      async () => {
        const user = userEvent.setup();
        postMock.mockRejectedValueOnce({
          response: {
            data: {
              message:
                'Only a submitted test result can be approved.',
            },
          },
        });
        renderResultView();
        await user.click(
          await screen.findByRole(
            'button',
            {
              name: 'Approve result',
            }
          )
        );
        expect(
          await screen.findByText(
            'Only a submitted test result can be approved.'
          )
        ).toBeInTheDocument();
      }
    );
  }
);
