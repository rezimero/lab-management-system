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
  reportPage,
  reportResource,
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
      role: 'admin',
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
            <div>Report detail route</div>
          }
        />
      </Routes>
    </MemoryRouter>
);
describe(
  'LaboratoryReports list',
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
      'loads and renders issued reports',
      async () => {
        renderPage();
        expect(
          await screen.findByText(
            reportResource.report_number
          )
        ).toBeInTheDocument();
        expect(getMock)
          .toHaveBeenCalledWith(
            '/laboratory-reports',
            {
              params: {
                page: 1,
                per_page: 25,
                sort: 'generated_at',
                direction: 'desc',
              },
            }
          );
        expect(
          screen.getByText(
            'Ada Lovelace'
          )
        ).toBeInTheDocument();
      }
    );
    it(
      'searches through backend filters',
      async () => {
        const user = userEvent.setup();
        renderPage();
        await screen.findByText(
          reportResource.report_number
        );
        await user.type(
          screen.getByRole(
            'textbox',
            {
              name: 'Search reports',
            }
          ),
          'Ada'
        );
        await user.click(
          screen.getByRole(
            'button',
            {
              name: 'Search',
            }
          )
        );
        await waitFor(() => {
          expect(getMock)
            .toHaveBeenLastCalledWith(
              '/laboratory-reports',
              {
                params: {
                  page: 1,
                  per_page: 25,
                  sort:
                    'generated_at',
                  direction: 'desc',
                  search: 'Ada',
                },
              }
            );
        });
      }
    );
    it(
      'opens the selected report',
      async () => {
        const user = userEvent.setup();
        renderPage();
        await user.click(
          await screen.findByText(
            reportResource.report_number
          )
        );
        expect(
          await screen.findByText(
            'Report detail route'
          )
        ).toBeInTheDocument();
      }
    );
  }
);
