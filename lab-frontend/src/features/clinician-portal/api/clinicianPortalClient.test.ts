import {
  afterEach,
  beforeEach,
  describe,
  expect,
  it,
  vi,
} from 'vitest';

interface RequestConfig {
  headers: Record<string, string>;
}

interface ClientError {
  response?: {
    status?: number;
  };
  config?: {
    url?: string;
  };
}

type RequestFulfilled = (
  config: RequestConfig
) => RequestConfig;

type ResponseFulfilled = <T>(
  response: T
) => T;

type ResponseRejected = (
  error: ClientError
) => Promise<never>;

const axiosHarness = vi.hoisted(() => {
  const callbacks: {
    requestFulfilled?: RequestFulfilled;
    responseFulfilled?: ResponseFulfilled;
    responseRejected?: ResponseRejected;
  } = {};

  const instance = {
    interceptors: {
      request: {
        use: vi.fn(
          (
            fulfilled: RequestFulfilled
          ) => {
            callbacks.requestFulfilled =
              fulfilled;
            return 0;
          }
        ),
      },
      response: {
        use: vi.fn(
          (
            fulfilled: ResponseFulfilled,
            rejected: ResponseRejected
          ) => {
            callbacks.responseFulfilled =
              fulfilled;
            callbacks.responseRejected =
              rejected;
            return 0;
          }
        ),
      },
    },
  };

  return {
    callbacks,
    instance,
    reset: () => {
      callbacks.requestFulfilled =
        undefined;
      callbacks.responseFulfilled =
        undefined;
      callbacks.responseRejected =
        undefined;
      instance.interceptors.request.use
        .mockClear();
      instance.interceptors.response.use
        .mockClear();
    },
  };
});

vi.mock('axios', () => ({
  default: {
    create: vi.fn(
      () => axiosHarness.instance
    ),
  },
}));

const loadInterceptors = async () => {
  await import('./clinicianPortalClient');

  const {
    requestFulfilled,
    responseRejected,
  } = axiosHarness.callbacks;

  if (
    !requestFulfilled
    || !responseRejected
  ) {
    throw new Error(
      'Clinician portal interceptors were not registered.'
    );
  }

  return {
    requestFulfilled,
    responseRejected,
  };
};

describe('clinician portal API client', () => {
  beforeEach(() => {
    vi.resetModules();
    axiosHarness.reset();
    sessionStorage.clear();
    localStorage.clear();
  });

  afterEach(() => {
    vi.unstubAllGlobals();
    sessionStorage.clear();
    localStorage.clear();
  });

  it('uses only the clinician portal token and tenant domain', async () => {
    localStorage.setItem(
      'token',
      'internal-staff-token'
    );

    sessionStorage.setItem(
      'clinician_portal_token',
      'clinician-token'
    );

    sessionStorage.setItem(
      'clinician_portal_tenant_domain',
      'development'
    );

    const { requestFulfilled } =
      await loadInterceptors();

    const config = requestFulfilled({
      headers: {},
    });

    expect(
      config.headers.Authorization
    ).toBe('Bearer clinician-token');

    expect(
      config.headers[
        'X-Tenant-Domain'
      ]
    ).toBe('development');
  });

  it('clears clinician portal authentication on protected 401 without touching internal auth', async () => {
    localStorage.setItem(
      'token',
      'internal-staff-token'
    );

    sessionStorage.setItem(
      'clinician_portal_token',
      'clinician-token'
    );

    sessionStorage.setItem(
      'clinician_portal_tenant_domain',
      'development'
    );

    const assign = vi.fn();

    vi.stubGlobal('window', {
      location: {
        pathname:
          '/clinician-portal/orders',
        assign,
      },
      sessionStorage,
    });

    const { responseRejected } =
      await loadInterceptors();

    const error: ClientError = {
      response: {
        status: 401,
      },
      config: {
        url: '/portal/clinician/orders',
      },
    };

    await expect(
      responseRejected(error)
    ).rejects.toBe(error);

    expect(
      sessionStorage.getItem(
        'clinician_portal_token'
      )
    ).toBeNull();

    expect(
      localStorage.getItem('token')
    ).toBe('internal-staff-token');

    expect(assign).toHaveBeenCalledWith(
      '/clinician-portal/login'
    );
  });
});
