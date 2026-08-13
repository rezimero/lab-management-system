import type {
  ReactNode,
} from 'react';

import {
  render,
  screen,
  waitFor,
} from '@testing-library/react';

import userEvent from '@testing-library/user-event';
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

import {
  fetchPortalAccessSubjects,
  issuePortalInvitation,
  revokePortalInvitation,
  setPortalAccountActive,
} from '../features/portal-access/api/portalAccessAdminApi';

import PortalAccess from './PortalAccess';

vi.mock(
  '../features/portal-access/api/portalAccessAdminApi',
  () => ({
    fetchPortalAccessSubjects: vi.fn(),
    issuePortalInvitation: vi.fn(),
    revokePortalInvitation: vi.fn(),
    setPortalAccountActive: vi.fn(),
  })
);

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

const fetchMock = vi.mocked(
  fetchPortalAccessSubjects
);
const issueMock = vi.mocked(
  issuePortalInvitation
);
const activeMock = vi.mocked(
  setPortalAccountActive
);

const patientRow = {
  subject_type: 'patient' as const,
  subject: {
    id: 14,
    name: 'Ada Patient',
    email: 'ada@example.test',
    identifier: 'MRN-ADA-14',
    is_active: true,
  },
  account: null,
};

describe('portal access administration', () => {
  beforeEach(() => {
    vi.clearAllMocks();

    fetchMock.mockResolvedValue({
      data: [patientRow],
      meta: {
        current_page: 1,
        last_page: 1,
        per_page: 10,
        total: 1,
      },
    });

    issueMock.mockResolvedValue({
      message: 'Portal invitation created successfully.',
      account: {
        uuid: 'account-uuid',
        email: 'portal@example.test',
        is_active: true,
        activated_at: null,
        last_login_at: null,
        created_at: null,
        updated_at: null,
        invitation: {
          uuid: 'invitation-uuid',
          status: 'pending',
          expires_at: '2099-01-01T00:00:00Z',
          used_at: null,
          revoked_at: null,
          created_at: null,
        },
      },
      activation: {
        token: 'plain-invitation-token',
        tenant_domain: 'development',
        path: '/portal/activate',
        expires_at: '2099-01-01T00:00:00Z',
      },
    });

    activeMock.mockResolvedValue({
      uuid: 'account-uuid',
      email: 'portal@example.test',
      is_active: false,
      activated_at: null,
      last_login_at: null,
      created_at: null,
      updated_at: null,
      invitation: null,
    });

    vi.mocked(revokePortalInvitation)
      .mockResolvedValue();
  });

  it('creates an invitation and displays the one-time activation link', async () => {
    const user = userEvent.setup();

    render(
      <MemoryRouter>
        <PortalAccess />
      </MemoryRouter>
    );

    expect(
      await screen.findByText('Ada Patient')
    ).toBeInTheDocument();

    await user.click(
      screen.getByRole('button', {
        name: 'Create invitation',
      })
    );

    const email = screen.getByLabelText(
      /^Portal login email/
    );
    await user.clear(email);
    await user.type(email, 'portal@example.test');
    await user.click(
      screen.getByRole('button', {
        name: 'Create invitation',
      })
    );

    await waitFor(() => {
      expect(issueMock).toHaveBeenCalledWith(
        'patient',
        14,
        'portal@example.test'
      );
    });

    expect(
      await screen.findByDisplayValue(
        /#token=plain-invitation-token/
      )
    ).toBeInTheDocument();
  });

  it('requests immediate session revocation when disabling an account', async () => {
    fetchMock.mockResolvedValue({
      data: [{
        ...patientRow,
        account: {
          uuid: 'account-uuid',
          email: 'portal@example.test',
          is_active: true,
          activated_at: '2026-08-11T10:00:00Z',
          last_login_at: null,
          created_at: null,
          updated_at: null,
          invitation: null,
        },
      }],
      meta: {
        current_page: 1,
        last_page: 1,
        per_page: 10,
        total: 1,
      },
    });

    const user = userEvent.setup();

    render(
      <MemoryRouter>
        <PortalAccess />
      </MemoryRouter>
    );

    await user.click(
      await screen.findByRole('button', {
        name: 'Disable',
      })
    );

    await waitFor(() => {
      expect(activeMock).toHaveBeenCalledWith(
        'patient',
        'account-uuid',
        false
      );
    });
  });
});
