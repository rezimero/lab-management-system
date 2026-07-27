import {
  render,
  screen,
} from '@testing-library/react';

import {
  describe,
  expect,
  it,
} from 'vitest';

import type {
  LaboratoryReportResource,
} from '../types/laboratoryReport.types';

import ReportSignatureFooter from
  './ReportSignatureFooter';

const report: LaboratoryReportResource = {
  uuid: 'report-uuid',
  report_number: 'RPT-1001',
  laboratory: {
    lab_name: 'Rezhel Laboratory',
    full_address: '12 Laboratory Road',
    director_name: 'Dr Ada Okafor',
    director_signature_url:
      '/storage/director-signature.png',
    license_number: 'LIC-1001',
    registration_number: 'REG-1001',
    report_footer:
      'Clinical correlation is advised.',
  },
  patient: {},
  order: {},
  results: [],
};

describe('ReportSignatureFooter', () => {
  it('renders the immutable signature snapshot', () => {
    render(
      <ReportSignatureFooter
        report={report}
      />
    );

    expect(
      screen.getByRole('img', {
        name: 'Director signature',
      })
    ).toHaveAttribute(
      'src',
      '/storage/director-signature.png'
    );

    expect(
      screen.getByText('Dr Ada Okafor')
    ).toBeInTheDocument();

    expect(
      screen.getByText(
        'Licence: LIC-1001 | Registration: REG-1001'
      )
    ).toBeInTheDocument();

    expect(
      screen.getByText(
        'Clinical correlation is advised.'
      )
    ).toBeInTheDocument();
  });

  it('does not invent a missing signature', () => {
    render(
      <ReportSignatureFooter
        report={{
          ...report,
          laboratory: {
            ...report.laboratory,
            director_signature_url: null,
          },
        }}
      />
    );

    expect(
      screen.queryByRole('img', {
        name: 'Director signature',
      })
    ).not.toBeInTheDocument();
  });
});