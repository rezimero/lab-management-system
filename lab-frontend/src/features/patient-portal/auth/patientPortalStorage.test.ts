import {
  beforeEach,
  describe,
  expect,
  it,
} from 'vitest';

import {
  clearPatientPortalSession,
  patientPortalSessionExpired,
  readPatientPortalSession,
  writePatientPortalSession,
} from './patientPortalStorage';

describe('patient portal session storage', () => {
  beforeEach(() => {
    sessionStorage.clear();
    localStorage.clear();
  });

  it('stores patient portal authentication only in sessionStorage', () => {
    localStorage.setItem(
      'token',
      'internal-staff-token'
    );

    writePatientPortalSession({
      token: 'patient-token',
      domain: 'development',
      expiresAt:
        '2099-01-01T00:00:00+00:00',
    });

    expect(
      sessionStorage.getItem(
        'patient_portal_token'
      )
    ).toBe('patient-token');

    expect(
      sessionStorage.getItem(
        'patient_portal_tenant_domain'
      )
    ).toBe('development');

    expect(
      localStorage.getItem('token')
    ).toBe('internal-staff-token');
  });

  it('clears only patient portal session keys', () => {
    localStorage.setItem(
      'token',
      'internal-staff-token'
    );

    writePatientPortalSession({
      token: 'patient-token',
      domain: 'development',
      expiresAt: null,
    });

    clearPatientPortalSession();

    expect(
      readPatientPortalSession()
    ).toBeNull();

    expect(
      localStorage.getItem('token')
    ).toBe('internal-staff-token');
  });

  it('recognises an expired stored portal token', () => {
    expect(
      patientPortalSessionExpired({
        token: 'patient-token',
        domain: 'development',
        expiresAt:
          '2000-01-01T00:00:00+00:00',
      })
    ).toBe(true);
  });

  it('rejects a malformed token expiry timestamp', () => {
    expect(
      patientPortalSessionExpired({
        token: 'patient-token',
        domain: 'development',
        expiresAt: 'not-a-timestamp',
      })
    ).toBe(true);
  });
});
