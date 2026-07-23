import {
  cleanup,
  fireEvent,
  render,
  screen,
  waitFor,
} from '@testing-library/react';

import userEvent from '@testing-library/user-event';

import {
  QueryClient,
  QueryClientProvider,
} from '@tanstack/react-query';

import {
  afterEach,
  beforeEach,
  describe,
  expect,
  it,
  vi,
} from 'vitest';

import AllTenants from './AllTenants';

const mocks = vi.hoisted(() => ({
  api: {
    get: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    delete: vi.fn(),
  },

  navigate: vi.fn(),
  startImpersonation: vi.fn(),

  toast: {
    success: vi.fn(),
    error: vi.fn(),
  },
}));

vi.mock('../../api/client', () => ({
  default: mocks.api,
}));

vi.mock('../../contexts/AuthContext', () => ({
  useAuth: () => ({
    startImpersonation:
      mocks.startImpersonation,
  }),
}));

vi.mock('react-hot-toast', () => ({
  default: mocks.toast,
}));

vi.mock(
  'react-router-dom',
  async (importOriginal) => {
    const actual =
      await importOriginal<
        typeof import('react-router-dom')
      >();

    return {
      ...actual,
      useNavigate: () => mocks.navigate,
    };
  }
);

const payloads = vi.hoisted(() => ({
  create: {
    name: 'New Laboratory',
    domain: 'new-laboratory',
    admin_name: 'New Administrator',
    admin_email:
      'administrator@example.test',
    admin_password: 'StrongPass!123',
  },

  update: {
    name: 'Alpha Laboratory Updated',
    domain: 'alpha-updated',
    is_active: true,
  },

  password: {
    email: 'admin@example.test',
    new_password: 'ResetPass!123',
  },
}));

vi.mock('./TenantFormDialog', () => ({
  default: ({
    open,
    tenant,
    submitting,
    serverError,
    onClose,
    onCreate,
    onUpdate,
  }: {
    open: boolean;
    tenant?: {
      id: number;
      name: string;
    } | null;
    submitting: boolean;
    serverError?: string | null;
    onClose: () => void;
    onCreate: (
      payload: typeof payloads.create
    ) => Promise<void>;
    onUpdate: (
      tenantId: number,
      payload: typeof payloads.update
    ) => Promise<void>;
  }) => {
    if (!open) {
      return null;
    }

    return (
      <section aria-label="Tenant form dialog">
        <h2>
          {tenant
            ? `Edit ${tenant.name}`
            : 'Create tenant dialog'}
        </h2>

        {serverError && (
          <p>{serverError}</p>
        )}

        <button
          type="button"
          onClick={onClose}
        >
          Close tenant form
        </button>

        {tenant ? (
          <button
            type="button"
            disabled={submitting}
            onClick={() => {
              void onUpdate(
                tenant.id,
                payloads.update
              );
            }}
          >
            Submit tenant update
          </button>
        ) : (
          <button
            type="button"
            disabled={submitting}
            onClick={() => {
              void onCreate(payloads.create);
            }}
          >
            Submit tenant creation
          </button>
        )}
      </section>
    );
  },
}));

vi.mock(
  './TenantPasswordResetDialog',
  () => ({
    default: ({
      open,
      tenant,
      submitting,
      serverError,
      onClose,
      onSubmit,
    }: {
      open: boolean;
      tenant: {
        id: number;
        name: string;
      } | null;
      submitting: boolean;
      serverError?: string | null;
      onClose: () => void;
      onSubmit: (
        tenantId: number,
        payload: typeof payloads.password
      ) => Promise<void>;
    }) => {
      if (!open || !tenant) {
        return null;
      }

      return (
        <section aria-label="Password reset dialog">
          <h2>
            Reset password for {tenant.name}
          </h2>

          {serverError && (
            <p>{serverError}</p>
          )}

          <button
            type="button"
            onClick={onClose}
          >
            Close password form
          </button>

          <button
            type="button"
            disabled={submitting}
            onClick={() => {
              void onSubmit(
                tenant.id,
                payloads.password
              );
            }}
          >
            Submit password reset
          </button>
        </section>
      );
    },
  })
);

interface TenantFixture {
  id: number;
  name: string;
  domain: string;
  database: string;
  is_active: boolean;
  trial_ends_at: string | null;
  created_at: string;
  stats: {
    users: number;
    patients: number;
    lab_tests: number;
    test_results: number;
  };
}

const alphaTenant: TenantFixture = {
  id: 7,
  name: 'Alpha Diagnostics',
  domain: 'alpha.example.test',
  database: 'tenant_alpha',
  is_active: true,
  trial_ends_at: null,
  created_at: '2026-07-01T00:00:00Z',
  stats: {
    users: 4,
    patients: 18,
    lab_tests: 6,
    test_results: 24,
  },
};

const betaTenant: TenantFixture = {
  id: 8,
  name: 'Beta Medical Lab',
  domain: 'beta.example.test',
  database: 'tenant_beta',
  is_active: false,
  trial_ends_at: null,
  created_at: '2026-07-02T00:00:00Z',
  stats: {
    users: 2,
    patients: 9,
    lab_tests: 3,
    test_results: 11,
  },
};

const createDeferred = <T,>() => {
  let resolve!: (value: T) => void;
  let reject!: (reason?: unknown) => void;

  const promise = new Promise<T>(
    (resolvePromise, rejectPromise) => {
      resolve = resolvePromise;
      reject = rejectPromise;
    }
  );

  return {
    promise,
    resolve,
    reject,
  };
};

const renderAllTenants = () => {
  const queryClient = new QueryClient({
    defaultOptions: {
      queries: {
        retry: false,
        gcTime: Infinity,
      },
      mutations: {
        retry: false,
      },
    },
  });

  const invalidateQueries = vi
    .spyOn(
      queryClient,
      'invalidateQueries'
    )
    .mockResolvedValue(undefined);

  render(
    <QueryClientProvider
      client={queryClient}
    >
      <AllTenants />
    </QueryClientProvider>
  );

  return {
    queryClient,
    invalidateQueries,
  };
};

const openTenantMenu = async (
  user: ReturnType<
    typeof userEvent.setup
  >,
  tenantName: string
) => {
  await user.click(
    await screen.findByRole(
      'button',
      {
        name: `Manage ${tenantName}`,
      }
    )
  );
};

describe('AllTenants characterization', () => {
  beforeEach(() => {
    vi.clearAllMocks();

    mocks.api.get.mockResolvedValue({
      data: {
        tenants: [
          alphaTenant,
          betaTenant,
        ],
      },
    });

    mocks.api.post.mockResolvedValue({
      data: {},
    });

    mocks.api.put.mockResolvedValue({
      data: {},
    });

    mocks.api.delete.mockResolvedValue({
      data: {},
    });

    mocks.startImpersonation
      .mockResolvedValue(undefined);
  });

  afterEach(() => {
    cleanup();
  });

  it('shows a loading surface while requesting the tenant portfolio', async () => {
    const deferred =
      createDeferred<{
        data: {
          tenants: TenantFixture[];
        };
      }>();

    mocks.api.get.mockReturnValue(
      deferred.promise
    );

    renderAllTenants();

    expect(mocks.api.get).toHaveBeenCalledWith(
      '/landlord/tenants'
    );

    expect(
      screen.queryByText(
        'Managed laboratories'
      )
    ).not.toBeInTheDocument();

    deferred.resolve({
      data: {
        tenants: [alphaTenant],
      },
    });

    expect(
      await screen.findByText(
        'Alpha Diagnostics'
      )
    ).toBeInTheDocument();
  });

  it('renders array-shaped tenant data and aggregate statistics', async () => {
    renderAllTenants();

    expect(
      await screen.findByText(
        'Alpha Diagnostics'
      )
    ).toBeInTheDocument();

    expect(
      screen.getByText(
        'Beta Medical Lab'
      )
    ).toBeInTheDocument();

    expect(
      screen.getByText(
        'alpha.example.test'
      )
    ).toBeInTheDocument();

    const maintainButtons =
      screen.getAllByRole('button', {
        name: 'Maintain',
      });

    expect(maintainButtons[0]).toBeEnabled();
    expect(maintainButtons[1]).toBeDisabled();
  });

  it('accepts the paginated tenant response shape', async () => {
    mocks.api.get.mockResolvedValue({
      data: {
        tenants: {
          data: [alphaTenant],
        },
      },
    });

    renderAllTenants();

    expect(
      await screen.findByText(
        'Alpha Diagnostics'
      )
    ).toBeInTheDocument();

    expect(
      screen.queryByText(
        'Beta Medical Lab'
      )
    ).not.toBeInTheDocument();
  });

  it('renders the empty portfolio state', async () => {
    mocks.api.get.mockResolvedValue({
      data: {
        tenants: [],
      },
    });

    renderAllTenants();

    expect(
      await screen.findByText(
        'No laboratories found'
      )
    ).toBeInTheDocument();

    expect(
      screen.getByText(
        /create a new laboratory or revise the search term/i
      )
    ).toBeInTheDocument();
  });

  it('shows a query error and retries the tenant request', async () => {
    const user = userEvent.setup();

    mocks.api.get
      .mockRejectedValueOnce(
        new Error('Network unavailable')
      )
      .mockResolvedValueOnce({
        data: {
          tenants: [alphaTenant],
        },
      });

    renderAllTenants();

    expect(
      await screen.findByText(
        /could not load the tenant portfolio/i
      )
    ).toBeInTheDocument();

    await user.click(
      screen.getByRole('button', {
        name: 'Retry',
      })
    );

    expect(
      await screen.findByText(
        'Alpha Diagnostics'
      )
    ).toBeInTheDocument();

    expect(mocks.api.get).toHaveBeenCalledTimes(2);
  });

  it('filters tenants case-insensitively by name, domain and database', async () => {
    const user = userEvent.setup();

    renderAllTenants();

    const search =
      await screen.findByPlaceholderText(
        'Search tenants'
      );

    await user.type(search, 'BETA');

    expect(
      screen.getByText('Beta Medical Lab')
    ).toBeInTheDocument();

    expect(
      screen.queryByText(
        'Alpha Diagnostics'
      )
    ).not.toBeInTheDocument();

    await user.clear(search);
    await user.type(
      search,
      'TENANT_ALPHA'
    );

    expect(
      screen.getByText('Alpha Diagnostics')
    ).toBeInTheDocument();

    expect(
      screen.queryByText(
        'Beta Medical Lab'
      )
    ).not.toBeInTheDocument();
  });

  it('opens the create dialog, submits the create endpoint and refreshes tenant caches', async () => {
    const user = userEvent.setup();
    const { invalidateQueries } =
      renderAllTenants();

    await screen.findByText(
      'Alpha Diagnostics'
    );

    await user.click(
      screen.getByRole('button', {
        name: 'Create laboratory',
      })
    );

    expect(
      screen.getByRole('heading', {
        name: 'Create tenant dialog',
      })
    ).toBeInTheDocument();

    await user.click(
      screen.getByRole('button', {
        name: 'Submit tenant creation',
      })
    );

    await waitFor(() => {
      expect(mocks.api.post)
        .toHaveBeenCalledWith(
          '/landlord/tenants',
          payloads.create
        );
    });

    expect(mocks.toast.success)
      .toHaveBeenCalledWith(
        'Laboratory tenant provisioned successfully.'
      );

    expect(invalidateQueries)
      .toHaveBeenNthCalledWith(1, {
        queryKey: [
          'rezhel-landlord-tenants',
        ],
      });

    expect(invalidateQueries)
      .toHaveBeenNthCalledWith(2, {
        queryKey: [
          'rezhel-landlord-dashboard',
        ],
      });

    await waitFor(() => {
      expect(
        screen.queryByLabelText(
          'Tenant form dialog'
        )
      ).not.toBeInTheDocument();
    });
  });

  it('opens the selected tenant in edit mode and submits the update endpoint', async () => {
    const user = userEvent.setup();

    renderAllTenants();

    await openTenantMenu(
      user,
      'Alpha Diagnostics'
    );

    await user.click(
      await screen.findByRole(
        'menuitem',
        {
          name: 'Edit tenant',
        }
      )
    );

    expect(
      screen.getByRole('heading', {
        name: 'Edit Alpha Diagnostics',
      })
    ).toBeInTheDocument();

    await user.click(
      screen.getByRole('button', {
        name: 'Submit tenant update',
      })
    );

    await waitFor(() => {
      expect(mocks.api.put)
        .toHaveBeenCalledWith(
          '/landlord/tenants/7',
          payloads.update
        );
    });

    expect(mocks.toast.success)
      .toHaveBeenCalledWith(
        'Tenant details updated.'
      );
  });

  it('suspends an active tenant after confirmation', async () => {
    const user = userEvent.setup();

    renderAllTenants();

    await openTenantMenu(
      user,
      'Alpha Diagnostics'
    );

    await user.click(
      await screen.findByRole(
        'menuitem',
        {
          name: 'Suspend tenant',
        }
      )
    );

    expect(
      screen.getByRole('heading', {
        name: 'Suspend laboratory tenant',
      })
    ).toBeInTheDocument();

    await user.click(
      screen.getByRole('button', {
        name: 'Suspend tenant',
      })
    );

    await waitFor(() => {
      expect(mocks.api.post)
        .toHaveBeenCalledWith(
          '/landlord/tenants/7/suspend'
        );
    });

    expect(mocks.toast.success)
      .toHaveBeenCalledWith(
        'Tenant suspended.'
      );
  });

  it('activates a suspended tenant after confirmation', async () => {
    const user = userEvent.setup();

    renderAllTenants();

    await openTenantMenu(
      user,
      'Beta Medical Lab'
    );

    await user.click(
      await screen.findByRole(
        'menuitem',
        {
          name: 'Activate tenant',
        }
      )
    );

    expect(
      screen.getByRole('heading', {
        name: 'Activate laboratory tenant',
      })
    ).toBeInTheDocument();

    await user.click(
      screen.getByRole('button', {
        name: 'Activate tenant',
      })
    );

    await waitFor(() => {
      expect(mocks.api.post)
        .toHaveBeenCalledWith(
          '/landlord/tenants/8/activate'
        );
    });

    expect(mocks.toast.success)
      .toHaveBeenCalledWith(
        'Tenant activated.'
      );
  });

  it('requires the exact tenant domain before permanent deletion', async () => {
    const user = userEvent.setup();

    renderAllTenants();

    await openTenantMenu(
      user,
      'Alpha Diagnostics'
    );

    await user.click(
      await screen.findByRole(
        'menuitem',
        {
          name: 'Delete tenant',
        }
      )
    );

    const deleteButton =
      screen.getByRole('button', {
        name: 'Delete permanently',
      });

    expect(deleteButton).toBeDisabled();

    const domainInput =
      screen.getByRole('textbox', {
        name: 'Tenant domain',
      });

    fireEvent.change(domainInput, {
      target: {
        value: 'alpha.example',
      },
    });

    expect(deleteButton).toBeDisabled();

    fireEvent.change(domainInput, {
      target: {
        value: 'alpha.example.test',
      },
    });

    expect(deleteButton).toBeEnabled();

    await user.click(deleteButton);

    await waitFor(() => {
      expect(mocks.api.delete)
        .toHaveBeenCalledWith(
          '/landlord/tenants/7'
        );
    });

    expect(mocks.toast.success)
      .toHaveBeenCalledWith(
        'Tenant deleted permanently.'
      );
  });

  it('opens the password dialog and submits the reset endpoint', async () => {
    const user = userEvent.setup();

    renderAllTenants();

    await openTenantMenu(
      user,
      'Alpha Diagnostics'
    );

    await user.click(
      await screen.findByRole(
        'menuitem',
        {
          name: 'Reset admin password',
        }
      )
    );

    expect(
      screen.getByRole('heading', {
        name: 'Reset password for Alpha Diagnostics',
      })
    ).toBeInTheDocument();

    await user.click(
      screen.getByRole('button', {
        name: 'Submit password reset',
      })
    );

    await waitFor(() => {
      expect(mocks.api.post)
        .toHaveBeenCalledWith(
          '/landlord/tenants/7/reset-admin-password',
          payloads.password
        );
    });

    expect(mocks.toast.success)
      .toHaveBeenCalledWith(
        'Administrator password reset successfully.'
      );
  });

  it('starts tenant maintenance and replaces navigation with the dashboard', async () => {
    const user = userEvent.setup();

    renderAllTenants();

    const maintainButtons =
      await screen.findAllByRole(
        'button',
        {
          name: 'Maintain',
        }
      );

    await user.click(maintainButtons[0]);

    await waitFor(() => {
      expect(
        mocks.startImpersonation
      ).toHaveBeenCalledWith(7);
    });

    expect(mocks.toast.success)
      .toHaveBeenCalledWith(
        'Maintenance access opened for Alpha Diagnostics.'
      );

    expect(mocks.navigate)
      .toHaveBeenCalledWith(
        '/dashboard',
        {
          replace: true,
        }
      );
  });

  it('surfaces status mutation failures without closing the confirmation', async () => {
    const user = userEvent.setup();

    mocks.api.post.mockRejectedValueOnce(
      new Error('Status change denied')
    );

    renderAllTenants();

    await openTenantMenu(
      user,
      'Alpha Diagnostics'
    );

    await user.click(
      await screen.findByRole(
        'menuitem',
        {
          name: 'Suspend tenant',
        }
      )
    );

    await user.click(
      screen.getByRole('button', {
        name: 'Suspend tenant',
      })
    );

    await waitFor(() => {
      expect(mocks.toast.error)
        .toHaveBeenCalledWith(
          'Status change denied'
        );
    });

    expect(
      screen.getByRole('heading', {
        name: 'Suspend laboratory tenant',
      })
    ).toBeInTheDocument();
  });
});
