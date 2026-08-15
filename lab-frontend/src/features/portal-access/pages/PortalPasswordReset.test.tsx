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
  completePortalPasswordReset,
  inspectPortalPasswordReset,
  requestPortalPasswordReset,
} from '../api/portalPublicApi';

import PortalPasswordReset from './PortalPasswordReset';

vi.mock('../api/portalPublicApi', () => ({
  completePortalPasswordReset: vi.fn(),
  inspectPortalPasswordReset: vi.fn(),
  requestPortalPasswordReset: vi.fn(),
}));

const completeMock = vi.mocked(
  completePortalPasswordReset
);
const inspectMock = vi.mocked(
  inspectPortalPasswordReset
);
const requestMock = vi.mocked(
  requestPortalPasswordReset
);

describe('portal password reset', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    window.history.replaceState(
      null,
      '',
      '/portal/reset-password?tenant=development&type=patient'
    );
  });

  it('inspects and consumes a one-time reset link', async () => {
    const user = userEvent.setup();
    window.history.replaceState(
      null,
      '',
      '/portal/reset-password?tenant=development&type=patient#token=raw-reset-token'
    );
    inspectMock.mockResolvedValue({
      account_type: 'patient',
      email_hint: 'p******@example.test',
      expires_at: '2099-01-01T00:00:00Z',
    });
    completeMock.mockResolvedValue({
      message: 'Password reset successfully.',
      account_type: 'patient',
    });

    render(
      <MemoryRouter initialEntries={[
        '/portal/reset-password?tenant=development&type=patient',
      ]}>
        <PortalPasswordReset />
      </MemoryRouter>
    );

    expect(
      await screen.findByText(/p\*+@example\.test/i)
    ).toBeInTheDocument();
    expect(inspectMock).toHaveBeenCalledWith(
      'development',
      'raw-reset-token'
    );

    await user.type(
      screen.getByLabelText(/^New password/),
      'ChangedPortal123!'
    );
    await user.type(
      screen.getByLabelText(/^Confirm password/),
      'ChangedPortal123!'
    );
    await user.click(screen.getByRole('button', {
      name: 'Reset password',
    }));

    await waitFor(() => {
      expect(completeMock).toHaveBeenCalledWith(
        'development',
        'raw-reset-token',
        'ChangedPortal123!',
        'ChangedPortal123!'
      );
    });

    expect(await screen.findByText(
      'Password reset complete'
    )).toBeInTheDocument();
  });

  it('shows only the generic response for a reset request', async () => {
    const user = userEvent.setup();
    requestMock.mockResolvedValue(
      'If an eligible portal account exists, a one-time password reset link will be sent.'
    );

    render(
      <MemoryRouter initialEntries={[
        '/portal/reset-password?tenant=development&type=patient',
      ]}>
        <PortalPasswordReset />
      </MemoryRouter>
    );

    await user.type(
      screen.getByLabelText(/^Portal email address/),
      'patient@example.test'
    );
    await user.click(screen.getByRole('button', {
      name: 'Send reset link',
    }));

    expect(requestMock).toHaveBeenCalledWith(
      'development',
      'patient',
      'patient@example.test'
    );
    expect(await screen.findByText(
      /If an eligible portal account exists/i
    )).toBeInTheDocument();
  });
});
