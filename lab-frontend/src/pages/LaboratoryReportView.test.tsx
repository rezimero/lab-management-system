import type { ReactNode } from 'react';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import api from '../api/client';
import LaboratoryReportView from './LaboratoryReportView';
import {
  reportResource,
  reportUuid,
} from './laboratory-report-test/reportFixtures';

vi.mock('../api/client', () => ({
  default: { get: vi.fn() },
}));

vi.mock('../components/Layout/Layout', () => ({
  default: ({
    children,
    title,
  }: {
    children: ReactNode;
    title: string;
  }) => <main><h1>{title}</h1>{children}</main>,
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
        element={<LaboratoryReportView />}
      />
      <Route
        path="/laboratory-reports"
        element={<div>Report list route</div>}
      />
    </Routes>
  </MemoryRouter>
);

describe('LaboratoryReportView', () => {
  beforeEach(() => {
    vi.clearAllMocks();

    getMock.mockResolvedValue({
      data: { data: reportResource },
    } as never);
  });

  it('renders a universal printable report', async () => {
    renderPage();

    expect(
      await screen.findByText(
        reportResource.report_number
      )
    ).toBeInTheDocument();

    expect(getMock).toHaveBeenCalledWith(
      `/laboratory-reports/${reportUuid}`
    );

    expect(
      screen.getByTestId('report-print-root')
    ).toHaveClass('report-print-root');

    expect(
      document.querySelector('.report-summary')
    ).toBeInTheDocument();

    expect(
      document.querySelector('.report-patient-order')
    ).toBeInTheDocument();

    expect(
      document.querySelector('.report-result-card')
    ).toBeInTheDocument();

    expect(
      document.querySelector(
        '.report-signature-footer'
      )
    ).toBeInTheDocument();

    const styles =
      screen.getByTestId(
        'report-print-styles'
      ).textContent ?? '';

    expect(styles).toContain('@page');
    expect(styles).not.toContain('size: A4');
    expect(styles).toContain(
      'repeat('
    );
    expect(styles).toContain(
      'auto-fit'
    );
    expect(styles).toContain(
      'minmax(min(42mm, 100%), 1fr)'
    );
    expect(styles).toContain(
      'minmax(min(70mm, 100%), 1fr)'
    );
    expect(styles).toContain(
      'position: static'
    );
    expect(styles).not.toContain(
      'position: absolute'
    );
    expect(styles).toContain(
      'display: table-header-group'
    );
    expect(styles).toContain(
      'break-inside: avoid-page'
    );
  });

  it('calls the browser print command', async () => {
    const user = userEvent.setup();
    const printMock = vi.fn();

    Object.defineProperty(window, 'print', {
      configurable: true,
      value: printMock,
    });

    renderPage();

    await user.click(
      await screen.findByRole(
        'button',
        { name: 'Print report' }
      )
    );

    expect(printMock).toHaveBeenCalledTimes(1);
  });

  it('surfaces a report loading error', async () => {
    getMock.mockRejectedValueOnce({
      response: {
        data: { message: 'Report not found' },
      },
    });

    renderPage();

    expect(
      await screen.findByText(
        'Report not found'
      )
    ).toBeInTheDocument();
  });
});