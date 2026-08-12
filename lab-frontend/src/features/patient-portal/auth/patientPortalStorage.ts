const TOKEN_KEY = 'patient_portal_token';
const DOMAIN_KEY = 'patient_portal_tenant_domain';
const EXPIRES_AT_KEY = 'patient_portal_expires_at';

export interface StoredPatientPortalSession {
  token: string;
  domain: string;
  expiresAt: string | null;
}

const storageAvailable = (): boolean => (
  typeof window !== 'undefined'
  && typeof window.sessionStorage !== 'undefined'
);

export const readPatientPortalSession =
  (): StoredPatientPortalSession | null => {
    if (!storageAvailable()) {
      return null;
    }

    const token =
      window.sessionStorage.getItem(TOKEN_KEY);

    const domain =
      window.sessionStorage.getItem(DOMAIN_KEY);

    if (!token || !domain) {
      return null;
    }

    return {
      token,
      domain,
      expiresAt:
        window.sessionStorage.getItem(
          EXPIRES_AT_KEY
        ),
    };
  };

export const writePatientPortalSession = (
  session: StoredPatientPortalSession
): void => {
  if (!storageAvailable()) {
    return;
  }

  window.sessionStorage.setItem(
    TOKEN_KEY,
    session.token
  );

  window.sessionStorage.setItem(
    DOMAIN_KEY,
    session.domain
  );

  if (session.expiresAt) {
    window.sessionStorage.setItem(
      EXPIRES_AT_KEY,
      session.expiresAt
    );
  } else {
    window.sessionStorage.removeItem(
      EXPIRES_AT_KEY
    );
  }
};

export const clearPatientPortalSession =
  (): void => {
    if (!storageAvailable()) {
      return;
    }

    window.sessionStorage.removeItem(
      TOKEN_KEY
    );

    window.sessionStorage.removeItem(
      DOMAIN_KEY
    );

    window.sessionStorage.removeItem(
      EXPIRES_AT_KEY
    );
  };

export const patientPortalSessionExpired = (
  session: StoredPatientPortalSession
): boolean => {
  if (!session.expiresAt) {
    return false;
  }

  const expiresAt =
    new Date(session.expiresAt).getTime();

  if (Number.isNaN(expiresAt)) {
    return true;
  }

  return expiresAt <= Date.now();
};
