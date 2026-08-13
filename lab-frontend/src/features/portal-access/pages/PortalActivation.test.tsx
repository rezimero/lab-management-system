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
  activatePortalInvitation,
  inspectPortalInvitation,
} from '../api/portalPublicApi';

import PortalActivation from './PortalActivation';

vi.mock('../api/portalPublicApi', () => ({
  activatePortalInvitation: vi.fn(),
  inspectPortalInvitation: vi.fn(),
}));

const inspectMock = vi.mocked(
  inspectPortalInvitation
);
const activateMock = vi.mocked(
  activatePortalInvitation
);

describe('portal invitation activation', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    window.history.replaceState(
      null,
      '',
      '/portal/activate?tenant=development#token=raw-activation-token'
    );

    inspectMock.mockResolvedValue({
      account_type: 'patient',
      email: 'patient@example.test',
      display_name: 'Ada Patient',
      expires_at: '2099-01-01T00:00:00Z',
    });

    activateMock.mockResolvedValue({
      message: 'Portal account activated successfully.',
      account_type: 'patient',
      email: 'patient@example.test',
    });
  });

  it('inspects a one-time link and establishes the password', async () => {
    const user = userEvent.setup();

    render(
      <MemoryRouter
        initialEntries={[
          '/portal/activate?tenant=development',
        ]}
      >
        <PortalActivation />
      </MemoryRouter>
    );

    expect(
      await screen.findByText(/Ada Patient/i)
    ).toBeInTheDocument();

    expect(inspectMock).toHaveBeenCalledWith(
      'development',
      'raw-activation-token'
    );

    await user.type(
      screen.getByLabelText(/^New password/),
      'ActivatedPortal123!'
    );
    await user.type(
      screen.getByLabelText(/^Confirm password/),
      'ActivatedPortal123!'
    );
    await user.click(
      screen.getByRole('button', {
        name: 'Activate account',
      })
    );

    await waitFor(() => {
      expect(activateMock).toHaveBeenCalledWith(
        'development',
        'raw-activation-token',
        'ActivatedPortal123!',
        'ActivatedPortal123!'
      );
    });

    expect(
      await screen.findByText(
        'Portal account activated'
      )
    ).toBeInTheDocument();
  });

  it('rejects an incomplete activation link without calling the API', async () => {
    window.history.replaceState(
      null,
      '',
      '/portal/activate'
    );

    render(
      <MemoryRouter
        initialEntries={['/portal/activate']}
      >
        <PortalActivation />
      </MemoryRouter>
    );

    expect(
      await screen.findByText(
        'This activation link is incomplete or invalid.'
      )
    ).toBeInTheDocument();

    expect(inspectMock).not.toHaveBeenCalled();
  });
});
