import {
  beforeEach,
  describe,
  expect,
  it,
} from 'vitest';

import {
  clearClinicianPortalSession,
  clinicianPortalSessionExpired,
  readClinicianPortalSession,
  writeClinicianPortalSession,
} from './clinicianPortalStorage';

describe('clinician portal session storage', () => {
  beforeEach(() => {
    sessionStorage.clear();
    localStorage.clear();
  });

  it('stores clinician portal authentication only in sessionStorage', () => {
    localStorage.setItem(
      'token',
      'internal-staff-token'
    );

    writeClinicianPortalSession({
      token: 'clinician-token',
      domain: 'development',
      expiresAt:
        '2099-01-01T00:00:00+00:00',
    });

    expect(
      sessionStorage.getItem(
        'clinician_portal_token'
      )
    ).toBe('clinician-token');

    expect(
      sessionStorage.getItem(
        'clinician_portal_tenant_domain'
      )
    ).toBe('development');

    expect(
      localStorage.getItem('token')
    ).toBe('internal-staff-token');
  });

  it('clears only clinician portal session keys', () => {
    localStorage.setItem(
      'token',
      'internal-staff-token'
    );

    writeClinicianPortalSession({
      token: 'clinician-token',
      domain: 'development',
      expiresAt: null,
    });

    clearClinicianPortalSession();

    expect(
      readClinicianPortalSession()
    ).toBeNull();

    expect(
      localStorage.getItem('token')
    ).toBe('internal-staff-token');
  });

  it('recognises an expired stored portal token', () => {
    expect(
      clinicianPortalSessionExpired({
        token: 'clinician-token',
        domain: 'development',
        expiresAt:
          '2000-01-01T00:00:00+00:00',
      })
    ).toBe(true);
  });

  it('rejects a malformed token expiry timestamp', () => {
    expect(
      clinicianPortalSessionExpired({
        token: 'clinician-token',
        domain: 'development',
        expiresAt: 'not-a-timestamp',
      })
    ).toBe(true);
  });
});
