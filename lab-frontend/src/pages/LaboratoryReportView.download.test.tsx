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

const createObjectUrlMock =
  vi.fn(() => 'blob:report-pdf');

const revokeObjectUrlMock =
  vi.fn();

const anchorClickMock =
  vi.fn();

const reportResponse = {
  data: {
    data: reportResource,
  },
};

const renderPage = () => render(
  <MemoryRouter
    initialEntries={[
      `/laboratory-reports/${reportUuid}`,
    ]}
  >
    <Routes>
      <Route
        path="/laboratory-reports/:id"
        element={<LaboratoryReportView />}
      />
    </Routes>
  </MemoryRouter>
);

describe(
  'LaboratoryReportView PDF download',
  () => {
    beforeEach(() => {
      vi.clearAllMocks();

      Object.defineProperty(
        URL,
        'createObjectURL',
        {
          configurable: true,
          value: createObjectUrlMock,
        }
      );

      Object.defineProperty(
        URL,
        'revokeObjectURL',
        {
          configurable: true,
          value: revokeObjectUrlMock,
        }
      );

      Object.defineProperty(
        HTMLAnchorElement.prototype,
        'click',
        {
          configurable: true,
          value: anchorClickMock,
        }
      );

      getMock.mockResolvedValue(
        reportResponse as never
      );
    });

    it(
      'downloads the server-generated PDF blob',
      async () => {
        const user = userEvent.setup();

        const pdfBlob = new Blob(
          ['%PDF-1.7'],
          {
            type: 'application/pdf',
          }
        );

        getMock
          .mockResolvedValueOnce(
            reportResponse as never
          )
          .mockResolvedValueOnce({
            data: pdfBlob,
            headers: {
              'content-disposition':
                (
                  'attachment; '
                  + `filename="${reportResource.report_number}.pdf"`
                ),
            },
          } as never);

        renderPage();

        await user.click(
          await screen.findByRole(
            'button',
            {
              name: 'Download PDF',
            }
          )
        );

        await waitFor(() => {
          expect(getMock)
            .toHaveBeenNthCalledWith(
              2,
              (
                `/laboratory-reports/`
                + `${reportUuid}/pdf`
              ),
              {
                responseType: 'blob',
              }
            );
        });

        expect(
          createObjectUrlMock
        ).toHaveBeenCalledWith(
          pdfBlob
        );

        expect(
          anchorClickMock
        ).toHaveBeenCalledTimes(1);

        const clickedAnchor =
          anchorClickMock.mock
            .contexts[0] as
              HTMLAnchorElement;

        expect(
          clickedAnchor.download
        ).toBe(
          `${reportResource.report_number}.pdf`
        );

        expect(
          revokeObjectUrlMock
        ).toHaveBeenCalledWith(
          'blob:report-pdf'
        );
      }
    );

    it(
      'surfaces a PDF download error',
      async () => {
        const user = userEvent.setup();

        getMock
          .mockResolvedValueOnce(
            reportResponse as never
          )
          .mockRejectedValueOnce({
            response: {
              data: {
                message:
                  'PDF download unavailable',
              },
            },
          });

        renderPage();

        await user.click(
          await screen.findByRole(
            'button',
            {
              name: 'Download PDF',
            }
          )
        );

        expect(
          await screen.findByText(
            'PDF download unavailable'
          )
        ).toBeInTheDocument();

        expect(
          createObjectUrlMock
        ).not.toHaveBeenCalled();
      }
    );
  }
);