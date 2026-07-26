import type {
  ReactNode,
} from 'react';
import {
  render,
  screen,
} from '@testing-library/react';
import userEvent from
  '@testing-library/user-event';
import {
  MemoryRouter,
} from 'react-router-dom';
import {
  beforeEach,
  describe,
  expect,
  it,
  vi,
} from 'vitest';
import api from '../api/client';
import LaboratoryReports from
  './LaboratoryReports';
import {
  orderUuid,
  reportPage,
} from './laboratory-report-test/reportFixtures';

vi.mock('../api/client', () => ({
  default: {
    get: vi.fn(),
    post: vi.fn(),
  },
}));
vi.mock('../contexts/AuthContext', () => ({
  useAuth: () => ({
    user: {
      role: 'doctor',
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
  'LaboratoryReports generation errors',
  {
    timeout: 30_000,
  },
  () => {
    beforeEach(() => {
      vi.clearAllMocks();
      getMock.mockResolvedValue({
        data: reportPage,
      } as never);
    });

    it(
      'surfaces backend eligibility errors',
      async () => {
        const user = userEvent.setup();

        postMock.mockRejectedValueOnce({
          response: {
            data: {
              errors: {
                laboratory_order: [
                  'A laboratory report can only be generated for a completed order.',
                ],
              },
            },
          },
        });

        render(
          <MemoryRouter>
            <LaboratoryReports />
          </MemoryRouter>
        );

        await user.click(
          await screen.findByRole(
            'button',
            {
              name: 'Generate report',
            }
          )
        );
        await user.type(
          screen.getByRole(
            'textbox',
            {
              name:
                'Laboratory order UUID',
            }
          ),
          orderUuid
        );
        await user.click(
          screen.getByRole(
            'button',
            {
              name: 'Generate report',
            }
          )
        );

        expect(
          await screen.findByText(
            'A laboratory report can only be generated for a completed order.'
          )
        ).toBeInTheDocument();
      }
    );
  }
);
