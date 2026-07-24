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
import LaboratoryReportView from
  './LaboratoryReportView';
import {
  reportResource,
  reportUuid,
} from './laboratory-report-test/reportFixtures';
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
const getMock = vi.mocked(api.get);
const renderPage = () => render(
  <MemoryRouter
    initialEntries={[
      `/laboratory-reports/${reportUuid}`,
    ]}
  >
    <Routes>
      <Route
        path="/laboratory-reports/:id"
        element={
          <LaboratoryReportView />
        }
      />
      <Route
        path="/laboratory-reports"
        element={
          <div>Report list route</div>
        }
      />
    </Routes>
  </MemoryRouter>
);
describe(
  'LaboratoryReportView',
  {
    timeout: 30_000,
  },
  () => {
    beforeEach(() => {
      vi.clearAllMocks();
      getMock.mockResolvedValue({
        data: {
          data: reportResource,
        },
      } as never);
    });
    it(
      'renders the immutable report snapshot',
      async () => {
        renderPage();
        expect(
          await screen.findByText(
            reportResource.report_number
          )
        ).toBeInTheDocument();
        expect(getMock)
          .toHaveBeenCalledWith(
            `/laboratory-reports/${reportUuid}`
          );
        expect(
          screen.getByRole(
            'heading',
            {
              name:
                'Clinical Diagnostics Laboratory',
            }
          )
        ).toBeInTheDocument();
        expect(
          screen.getByText(
            'Ada Lovelace'
          )
        ).toBeInTheDocument();
        expect(
          screen.getByText(
            'Complete Blood Count'
          )
        ).toBeInTheDocument();
        expect(
          screen.getByText(
            'Haemoglobin'
          )
        ).toBeInTheDocument();
      }
    );
    it(
      'prints the report',
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
        await user.click(
          await screen.findByRole(
            'button',
            {
              name: 'Print report',
            }
          )
        );
        expect(printMock)
          .toHaveBeenCalledTimes(1);
      }
    );
    it(
      'surfaces a report loading error',
      async () => {
        getMock.mockRejectedValueOnce({
          response: {
            data: {
              message:
                'Report not found',
            },
          },
        });
        renderPage();
        expect(
          await screen.findByText(
            'Report not found'
          )
        ).toBeInTheDocument();
      }
    );
  }
);
