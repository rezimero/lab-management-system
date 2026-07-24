import type {
  ReactNode,
} from 'react';
import {
  render,
  screen,
  waitFor,
} from '@testing-library/react';
import userEvent from
  '@testing-library/user-event';
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
import LaboratoryReports from
  './LaboratoryReports';
import {
  orderUuid,
  reportPage,
  reportResource,
} from './laboratory-report-test/reportFixtures';

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
const renderPage = () => render(
  <MemoryRouter
    initialEntries={[
      '/laboratory-reports',
    ]}
  >
    <Routes>
      <Route
        path="/laboratory-reports"
        element={
          <LaboratoryReports />
        }
      />
      <Route
        path="/laboratory-reports/:id"
        element={
          <div>Generated report route</div>
        }
      />
    </Routes>
  </MemoryRouter>
);

describe(
  'LaboratoryReports generation',
  {
    timeout: 30_000,
  },
  () => {
    beforeEach(() => {
      vi.clearAllMocks();
      authState.role =
        'lab_technician';
      getMock.mockResolvedValue({
        data: reportPage,
      } as never);
      postMock.mockResolvedValue({
        data: {
          data: reportResource,
        },
      } as never);
    });

    it(
      'generates from a completed order UUID',
      async () => {
        const user = userEvent.setup();
        renderPage();

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

        await waitFor(() => {
          expect(postMock)
            .toHaveBeenCalledWith(
              `/laboratory-orders/${orderUuid}/report`
            );
        });
        expect(
          await screen.findByText(
            'Generated report route'
          )
        ).toBeInTheDocument();
      }
    );

    it(
      'does not expose generation to receptionists',
      async () => {
        authState.role =
          'receptionist';
        renderPage();
        await screen.findByText(
          reportResource.report_number
        );
        expect(
          screen.queryByRole(
            'button',
            {
              name: 'Generate report',
            }
          )
        ).not.toBeInTheDocument();
      }
    );
  }
);
