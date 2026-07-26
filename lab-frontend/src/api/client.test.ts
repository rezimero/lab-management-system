import {
  afterEach,
  beforeEach,
  describe,
  expect,
  it,
  vi,
} from 'vitest';

import {
  SERVER_ERROR_MESSAGE,
} from './serverErrorSanitizer';
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
    requestRejected?: (
      error: unknown
    ) => Promise<never>;
    responseFulfilled?: ResponseFulfilled;
    responseRejected?: ResponseRejected;
  } = {};

  const instance = {
    interceptors: {
      request: {
        use: vi.fn(
          (
            fulfilled: RequestFulfilled,
            rejected: (
              error: unknown
            ) => Promise<never>
          ) => {
            callbacks.requestFulfilled =
              fulfilled;
            callbacks.requestRejected =
              rejected;

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
      callbacks.requestRejected =
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

const authenticationStorageKeys = [
  'token',
  'user',
  'tenant',
  'tenant_domain',
  'is_superadmin',
  'is_impersonating',
  'role',
  'rezhel_platform_token',
  'rezhel_platform_user',
] as const;

const seedAuthenticationStorage =
  (): void => {
    authenticationStorageKeys.forEach(
      (key) => {
        localStorage.setItem(
          key,
          `${key}-value`
        );
      }
    );
  };

const loadClientInterceptors =
  async () => {
    await import('./client');

    expect(
      axiosHarness.instance.interceptors
        .request.use
    ).toHaveBeenCalledTimes(1);

    expect(
      axiosHarness.instance.interceptors
        .response.use
    ).toHaveBeenCalledTimes(1);

    const {
      requestFulfilled,
      responseFulfilled,
      responseRejected,
    } = axiosHarness.callbacks;

    if (
      !requestFulfilled ||
      !responseFulfilled ||
      !responseRejected
    ) {
      throw new Error(
        'Axios client interceptors were not registered.'
      );
    }

    return {
      requestFulfilled,
      responseFulfilled,
      responseRejected,
    };
  };

const createNavigationWindow = (
  pathname: string
) => {
  const assign = vi.fn();

  vi.stubGlobal('window', {
    location: {
      pathname,
      assign,
    },
  });

  return assign;
};

describe('API client interceptor characterization', () => {
  beforeEach(() => {
    vi.resetModules();
    axiosHarness.reset();
    localStorage.clear();
  });

  afterEach(() => {
    vi.unstubAllGlobals();
    localStorage.clear();
  });

  it('adds the active bearer token to requests', async () => {
    localStorage.setItem(
      'token',
      'tenant-token'
    );

    const { requestFulfilled } =
      await loadClientInterceptors();

    const config = requestFulfilled({
      headers: {},
    });

    expect(
      config.headers.Authorization
    ).toBe('Bearer tenant-token');
  });

  it('removes a stale authorization header without a token', async () => {
    const { requestFulfilled } =
      await loadClientInterceptors();

    const config = requestFulfilled({
      headers: {
        Authorization:
          'Bearer stale-token',
      },
    });

    expect(
      config.headers
    ).not.toHaveProperty(
      'Authorization'
    );
  });

  it('excludes tenant context from superadmin requests', async () => {
    localStorage.setItem(
      'token',
      'platform-token'
    );

    localStorage.setItem(
      'is_superadmin',
      'true'
    );

    localStorage.setItem(
      'tenant_domain',
      'tenant.example.test'
    );

    const { requestFulfilled } =
      await loadClientInterceptors();

    const config = requestFulfilled({
      headers: {
        'X-Tenant-Domain':
          'stale.example.test',
      },
    });

    expect(
      config.headers.Authorization
    ).toBe('Bearer platform-token');

    expect(
      config.headers
    ).not.toHaveProperty(
      'X-Tenant-Domain'
    );
  });

  it('adds the stored tenant domain to tenant requests', async () => {
    localStorage.setItem(
      'tenant_domain',
      'tenant.example.test'
    );

    localStorage.setItem(
      'is_superadmin',
      'false'
    );

    const { requestFulfilled } =
      await loadClientInterceptors();

    const config = requestFulfilled({
      headers: {},
    });

    expect(
      config.headers[
        'X-Tenant-Domain'
      ]
    ).toBe('tenant.example.test');
  });

  it('recovers and normalizes the domain from stored tenant data', async () => {
    localStorage.setItem(
      'tenant',
      JSON.stringify({
        domain:
          'LABORATORY.Example.Test',
      })
    );

    const { requestFulfilled } =
      await loadClientInterceptors();

    const config = requestFulfilled({
      headers: {},
    });

    expect(
      config.headers[
        'X-Tenant-Domain'
      ]
    ).toBe(
      'laboratory.example.test'
    );

    expect(
      localStorage.getItem(
        'tenant_domain'
      )
    ).toBe(
      'laboratory.example.test'
    );
  });

  it('removes malformed tenant data during domain recovery', async () => {
    localStorage.setItem(
      'tenant',
      '{invalid-json'
    );

    const { requestFulfilled } =
      await loadClientInterceptors();

    const config = requestFulfilled({
      headers: {
        'X-Tenant-Domain':
          'stale.example.test',
      },
    });

    expect(
      localStorage.getItem('tenant')
    ).toBeNull();

    expect(
      localStorage.getItem(
        'tenant_domain'
      )
    ).toBeNull();

    expect(
      config.headers
    ).not.toHaveProperty(
      'X-Tenant-Domain'
    );
  });

  it('passes successful responses through unchanged', async () => {
    const { responseFulfilled } =
      await loadClientInterceptors();

    const response = {
      data: {
        ok: true,
      },
    };

    expect(
      responseFulfilled(response)
    ).toBe(response);
  });

  it('clears authentication and redirects after a normal 401 response', async () => {
    seedAuthenticationStorage();

    const assign =
      createNavigationWindow(
        '/patients'
      );

    const { responseRejected } =
      await loadClientInterceptors();

    const error: ClientError = {
      response: {
        status: 401,
      },

      config: {
        url: '/patients',
      },
    };

    await expect(
      responseRejected(error)
    ).rejects.toBe(error);

    authenticationStorageKeys.forEach(
      (key) => {
        expect(
          localStorage.getItem(key)
        ).toBeNull();
      }
    );

    expect(assign).toHaveBeenCalledWith(
      '/login'
    );
  });

  it('does not force another redirect for a login 401 response', async () => {
    seedAuthenticationStorage();

    const assign =
      createNavigationWindow(
        '/workspace'
      );

    const { responseRejected } =
      await loadClientInterceptors();

    const error: ClientError = {
      response: {
        status: 401,
      },

      config: {
        url: '/login',
      },
    };

    await expect(
      responseRejected(error)
    ).rejects.toBe(error);

    authenticationStorageKeys.forEach(
      (key) => {
        expect(
          localStorage.getItem(key)
        ).toBeNull();
      }
    );

    expect(assign).not.toHaveBeenCalled();
  });

  it('does not force another redirect for a registration 401 response', async () => {
    seedAuthenticationStorage();

    const assign =
      createNavigationWindow(
        '/workspace'
      );

    const { responseRejected } =
      await loadClientInterceptors();

    const error: ClientError = {
      response: {
        status: 401,
      },

      config: {
        url: '/register',
      },
    };

    await expect(
      responseRejected(error)
    ).rejects.toBe(error);

    authenticationStorageKeys.forEach(
      (key) => {
        expect(
          localStorage.getItem(key)
        ).toBeNull();
      }
    );

    expect(assign).not.toHaveBeenCalled();
  });

  it('does not redirect again when already on the login page', async () => {
    seedAuthenticationStorage();

    const assign =
      createNavigationWindow('/login');

    const { responseRejected } =
      await loadClientInterceptors();

    const error: ClientError = {
      response: {
        status: 401,
      },

      config: {
        url: '/patients',
      },
    };

    await expect(
      responseRejected(error)
    ).rejects.toBe(error);

    expect(assign).not.toHaveBeenCalled();
  });

  it('preserves authentication during an impersonated logout 401 response', async () => {
    seedAuthenticationStorage();

    localStorage.setItem(
      'is_impersonating',
      'true'
    );

    const assign =
      createNavigationWindow(
        '/dashboard'
      );

    const { responseRejected } =
      await loadClientInterceptors();

    const error: ClientError = {
      response: {
        status: 401,
      },

      config: {
        url: '/logout',
      },
    };

    await expect(
      responseRejected(error)
    ).rejects.toBe(error);

    expect(
      localStorage.getItem('token')
    ).toBe('token-value');

    expect(
      localStorage.getItem(
        'rezhel_platform_token'
      )
    ).toBe(
      'rezhel_platform_token-value'
    );

    expect(assign).not.toHaveBeenCalled();
  });

  it('masks 5xx response data before rejecting', async () => {
    seedAuthenticationStorage();

    const assign =
      createNavigationWindow('/patients');

    const { responseRejected } =
      await loadClientInterceptors();

    const error = {
      response: {
        status: 500,
        data: {
          message:
            'SQLSTATE[42S02]: table missing',
          exception:
            'Illuminate Database QueryException',
        },
      },

      config: {
        url:
          '/laboratory-orders/order/report',
      },
    };

    await expect(
      responseRejected(error)
    ).rejects.toBe(error);

    expect(error.response.data).toEqual({
      message: SERVER_ERROR_MESSAGE,
    });

    expect(
      JSON.stringify(error.response.data)
    ).not.toContain('SQLSTATE');

    expect(assign).not.toHaveBeenCalled();
  });
  it('leaves authentication untouched for non-401 errors', async () => {
    seedAuthenticationStorage();

    const assign =
      createNavigationWindow(
        '/patients'
      );

    const { responseRejected } =
      await loadClientInterceptors();

    const error: ClientError = {
      response: {
        status: 500,
      },

      config: {
        url: '/patients',
      },
    };

    await expect(
      responseRejected(error)
    ).rejects.toBe(error);

    authenticationStorageKeys.forEach(
      (key) => {
        expect(
          localStorage.getItem(key)
        ).toBe(`${key}-value`);
      }
    );

    expect(assign).not.toHaveBeenCalled();
  });
});
