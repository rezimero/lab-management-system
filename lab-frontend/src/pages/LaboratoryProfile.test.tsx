import type {
  ReactNode,
} from 'react';

import {
  fireEvent,
  render,
  screen,
  waitFor,
} from '@testing-library/react';

import {
  beforeEach,
  describe,
  expect,
  it,
  vi,
} from 'vitest';

import toast from 'react-hot-toast';

import api from '../api/client';

import LaboratoryProfile from
  './LaboratoryProfile';

vi.mock('../api/client', () => ({
  default: {
    get: vi.fn(),
    put: vi.fn(),
    post: vi.fn(),
  },
}));

vi.mock(
  '../components/Layout/Layout',
  () => ({
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
  })
);

vi.mock('react-hot-toast', () => ({
  default: {
    success: vi.fn(),
    error: vi.fn(),
  },
}));

const profile = {
  lab_name: 'Rezhel Diagnostic Centre',
  tagline: 'Trusted laboratory care',
  primary_color: '#1976D2',
  secondary_color: '#424242',
  accent_color: '#2E7D32',
  email: 'lab@example.test',
  phone: '+2348012345678',
  website: 'https://lab.example.test',
  address: '12 Laboratory Road',
  city: 'Lagos',
  state: 'Lagos',
  country: 'Nigeria',
  full_address:
    '12 Laboratory Road, Lagos, Lagos, Nigeria',
  license_number: 'LIC-1001',
  registration_number: 'REG-1001',
  tin: null,
  report_footer: 'Clinical correlation is advised.',
  director_name: 'Dr Ada Okafor',
  director_qualification: 'MBBS, FMCPath',
  director_registration_number: 'MDCN-12345',
  signatory_designation: 'Consultant Pathologist',
  accreditation_details:
    'ISO 15189 accredited laboratory.',
  invoice_footer: null,
  bank_name: null,
  account_name: null,
  account_number: null,
  logo_url: null,
  favicon_url: null,
  director_signature_url: null,
  created_at: null,
  updated_at: null,
};

const getMock = vi.mocked(api.get);
const putMock = vi.mocked(api.put);
const postMock = vi.mocked(api.post);
const successMock =
  vi.mocked(toast.success);

describe('LaboratoryProfile', () => {
  beforeEach(() => {
    vi.clearAllMocks();

    getMock.mockResolvedValue({
      data: {
        success: true,
        data: profile,
      },
    } as never);

    putMock.mockResolvedValue({
      data: {
        success: true,
        message:
          'Laboratory profile updated successfully.',
        data: {
          ...profile,
          lab_name:
            'Rezhel Clinical Laboratory',
        },
      },
    } as never);

    postMock.mockResolvedValue({
      data: {
        success: true,
        message:
          'Laboratory logo uploaded successfully.',
        data: {
          ...profile,
          logo_url: '/storage/logo.png',
        },
      },
    } as never);
  });

  it('loads and updates profile details', async () => {
    render(<LaboratoryProfile />);

    const name = await screen.findByLabelText(
      /Laboratory name/i
    );

    expect(name).toHaveValue(
      'Rezhel Diagnostic Centre'
    );

    const primaryColour =
      screen.getByLabelText(
        'Primary colour'
      );

    expect(primaryColour).toHaveValue(
      '#1976D2'
    );

    fireEvent.change(primaryColour, {
      target: {
        value: '#7c3aed',
      },
    });

    fireEvent.change(name, {
      target: {
        value:
          'Rezhel Clinical Laboratory',
      },
    });

    fireEvent.click(
      screen.getByRole('button', {
        name: 'Save changes',
      })
    );

    await waitFor(() => {
      expect(putMock).toHaveBeenCalledWith(
        '/tenant/profile',
        expect.objectContaining({
          lab_name:
            'Rezhel Clinical Laboratory',
          primary_color: '#7C3AED',
          secondary_color: '#424242',
          accent_color: '#2E7D32',
          director_name: 'Dr Ada Okafor',
          director_qualification:
            'MBBS, FMCPath',
          director_registration_number:
            'MDCN-12345',
          signatory_designation:
            'Consultant Pathologist',
          accreditation_details:
            'ISO 15189 accredited laboratory.',
        })
      );
    });

    expect(successMock).toHaveBeenCalledWith(
      'Laboratory profile updated successfully.'
    );
  });


  it('blocks malformed tenant brand colours', async () => {
    render(<LaboratoryProfile />);

    const primaryColour =
      await screen.findByLabelText(
        'Primary colour'
      );

    fireEvent.change(primaryColour, {
      target: {
        value: 'blue',
      },
    });

    fireEvent.click(
      screen.getByRole('button', {
        name: 'Save changes',
      })
    );

    const colourErrors =
      await screen.findAllByText(
        /Primary colour must use a six-digit HEX/i
      );

    expect(colourErrors).toHaveLength(2);
    expect(putMock).not.toHaveBeenCalled();
  });
  it('uploads the laboratory logo', async () => {

    render(<LaboratoryProfile />);

    const input =
      await screen.findByLabelText(
        'Upload laboratory logo'
      );

    const file = new File(
      ['image-bytes'],
      'logo.png',
      {
        type: 'image/png',
      }
    );

    fireEvent.change(input, {
      target: {
        files: [file],
      },
    });

    await waitFor(() => {
      expect(postMock).toHaveBeenCalledWith(
        '/tenant/profile/logo',
        expect.any(FormData)
      );
    });

    const payload =
      postMock.mock.calls[0][1] as FormData;

    expect(payload.get('file')).toBe(file);

    expect(
      await screen.findByRole('img', {
        name: 'Laboratory logo',
      })
    ).toHaveAttribute(
      'src',
      '/storage/logo.png'
    );
  });
});