const TOKEN_KEY = 'clinician_portal_token';
const DOMAIN_KEY = 'clinician_portal_tenant_domain';
const EXPIRES_AT_KEY = 'clinician_portal_expires_at';

export interface StoredClinicianPortalSession {
  token: string;
  domain: string;
  expiresAt: string | null;
}

const storageAvailable = (): boolean => (
  typeof window !== 'undefined'
  && typeof window.sessionStorage !== 'undefined'
);

export const readClinicianPortalSession =
  (): StoredClinicianPortalSession | null => {
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

export const writeClinicianPortalSession = (
  session: StoredClinicianPortalSession
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

export const clearClinicianPortalSession =
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

export const clinicianPortalSessionExpired = (
  session: StoredClinicianPortalSession
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
