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
  role: 'lab_technician',
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
  'TestResultView submission workflow',
  {
    timeout: 30_000,
  },
  () => {
    beforeEach(() => {
      vi.clearAllMocks();
      authState.role =
        'lab_technician';

      getMock.mockResolvedValue({
        data: {
          data: resultWithStatus(
            'draft'
          ),
        },
      } as never);

      postMock.mockResolvedValue({
        data: {
          data: resultWithStatus(
            'submitted'
          ),
        },
      } as never);
    });

    it(
      'submits a draft for review',
      async () => {
        const user = userEvent.setup();

        renderResultView();

        await user.click(
          await screen.findByRole(
            'button',
            {
              name: 'Submit for review',
            }
          )
        );

        await waitFor(() => {
          expect(postMock)
            .toHaveBeenCalledWith(
              `/test-results/${resultUuid}/submit`
            );
        });

        expect(
          await screen.findByText(
            'Submitted'
          )
        ).toBeInTheDocument();

        expect(
          screen.queryByRole(
            'button',
            {
              name: 'Submit for review',
            }
          )
        ).not.toBeInTheDocument();
      }
    );

    it(
      'navigates to edit a draft',
      async () => {
        const user = userEvent.setup();

        renderResultView();

        await user.click(
          await screen.findByRole(
            'button',
            {
              name: 'Edit result',
            }
          )
        );

        expect(
          await screen.findByText(
            'Result edit route'
          )
        ).toBeInTheDocument();
      }
    );

    it(
      'does not show review actions to a technician',
      async () => {
        getMock.mockResolvedValueOnce({
          data: {
            data: resultWithStatus(
              'submitted'
            ),
          },
        } as never);

        renderResultView();

        await screen.findByText(
          'Submitted'
        );

        expect(
          screen.queryByRole(
            'button',
            {
              name: 'Approve result',
            }
          )
        ).not.toBeInTheDocument();

        expect(
          screen.queryByRole(
            'button',
            {
              name: 'Reject result',
            }
          )
        ).not.toBeInTheDocument();
      }
    );
  }
);
