import {
  useMemo,
  useState,
} from 'react';

import type {
  MouseEvent,
} from 'react';

import {
  useMutation,
  useQuery,
  useQueryClient,
} from '@tanstack/react-query';

import toast from 'react-hot-toast';

import {
  useNavigate,
} from 'react-router-dom';

import {
  useAuth,
} from '../../contexts/AuthContext';

import {
  changeTenantStatus,
  createTenant,
  deleteTenant,
  fetchTenants,
  getTenantErrorMessage,
  resetTenantAdminPassword,
  updateTenant,
} from './tenantManagementApi';

import {
  calculateTenantTotals,
  countActiveTenants,
  filterTenants,
} from './tenantManagementSelectors';

import type {
  ConfirmationAction,
  CreateTenantPayload,
  PasswordResetPayload,
  TenantRecord,
  TenantStatusAction,
  UpdateTenantPayload,
} from './tenantManagementTypes';

const tenantQueryKey = [
  'rezhel-landlord-tenants',
] as const;

const dashboardQueryKey = [
  'rezhel-landlord-dashboard',
] as const;

export const useTenantManagement = () => {
  const navigate = useNavigate();
  const queryClient =
    useQueryClient();

  const {
    startImpersonation,
  } = useAuth();

  const [
    search,
    setSearch,
  ] = useState('');

  const [
    formOpen,
    setFormOpen,
  ] = useState(false);

  const [
    editingTenant,
    setEditingTenant,
  ] = useState<TenantRecord | null>(
    null
  );

  const [
    passwordTenant,
    setPasswordTenant,
  ] = useState<TenantRecord | null>(
    null
  );

  const [
    confirmationTenant,
    setConfirmationTenant,
  ] = useState<TenantRecord | null>(
    null
  );

  const [
    confirmationAction,
    setConfirmationAction,
  ] = useState<ConfirmationAction>(
    null
  );

  const [
    deleteConfirmation,
    setDeleteConfirmation,
  ] = useState('');

  const [
    formError,
    setFormError,
  ] = useState<string | null>(null);

  const [
    passwordError,
    setPasswordError,
  ] = useState<string | null>(null);

  const [
    menuAnchor,
    setMenuAnchor,
  ] = useState<HTMLElement | null>(
    null
  );

  const [
    menuTenant,
    setMenuTenant,
  ] = useState<TenantRecord | null>(
    null
  );

  const {
    data: tenants = [],
    isPending,
    isError,
    isFetching,
    refetch,
  } = useQuery({
    queryKey: tenantQueryKey,
    queryFn: fetchTenants,
    staleTime: 20_000,
  });

  const refreshTenants =
    async (): Promise<void> => {
      await queryClient.invalidateQueries({
        queryKey: tenantQueryKey,
      });

      await queryClient.invalidateQueries({
        queryKey: dashboardQueryKey,
      });
    };

  const createMutation =
    useMutation({
      mutationFn: createTenant,

      onSuccess: async () => {
        toast.success(
          'Laboratory tenant provisioned successfully.'
        );

        setFormOpen(false);
        setEditingTenant(null);
        setFormError(null);

        await refreshTenants();
      },

      onError: (error) => {
        setFormError(
          getTenantErrorMessage(error)
        );
      },
    });

  const updateMutation =
    useMutation({
      mutationFn: async ({
        tenantId,
        payload,
      }: {
        tenantId: number;
        payload: UpdateTenantPayload;
      }) => {
        return updateTenant(
          tenantId,
          payload
        );
      },

      onSuccess: async () => {
        toast.success(
          'Tenant details updated.'
        );

        setFormOpen(false);
        setEditingTenant(null);
        setFormError(null);

        await refreshTenants();
      },

      onError: (error) => {
        setFormError(
          getTenantErrorMessage(error)
        );
      },
    });

  const statusMutation =
    useMutation({
      mutationFn: async ({
        tenant,
        action,
      }: {
        tenant: TenantRecord;
        action: TenantStatusAction;
      }) => {
        return changeTenantStatus(
          tenant,
          action
        );
      },

      onSuccess: async (
        _response,
        variables
      ) => {
        toast.success(
          variables.action ===
            'suspend'
            ? 'Tenant suspended.'
            : 'Tenant activated.'
        );

        setConfirmationTenant(null);
        setConfirmationAction(null);

        await refreshTenants();
      },

      onError: (error) => {
        toast.error(
          getTenantErrorMessage(error)
        );
      },
    });

  const deleteMutation =
    useMutation({
      mutationFn: deleteTenant,

      onSuccess: async () => {
        toast.success(
          'Tenant deleted permanently.'
        );

        setConfirmationTenant(null);
        setConfirmationAction(null);
        setDeleteConfirmation('');

        await refreshTenants();
      },

      onError: (error) => {
        toast.error(
          getTenantErrorMessage(error)
        );
      },
    });

  const passwordMutation =
    useMutation({
      mutationFn: async ({
        tenantId,
        payload,
      }: {
        tenantId: number;
        payload: PasswordResetPayload;
      }) => {
        return resetTenantAdminPassword(
          tenantId,
          payload
        );
      },

      onSuccess: () => {
        toast.success(
          'Administrator password reset successfully.'
        );

        setPasswordTenant(null);
        setPasswordError(null);
      },

      onError: (error) => {
        setPasswordError(
          getTenantErrorMessage(error)
        );
      },
    });

  const impersonationMutation =
    useMutation({
      mutationFn: async (
        tenant: TenantRecord
      ) => {
        await startImpersonation(
          tenant.id
        );

        return tenant;
      },

      onSuccess: (tenant) => {
        toast.success(
          `Maintenance access opened for ${tenant.name}.`
        );

        navigate('/dashboard', {
          replace: true,
        });
      },

      onError: (error) => {
        toast.error(
          getTenantErrorMessage(error)
        );
      },
    });

  const filteredTenants =
    useMemo(() => {
      return filterTenants(
        tenants,
        search
      );
    }, [search, tenants]);

  const totals = useMemo(() => {
    return calculateTenantTotals(
      tenants
    );
  }, [tenants]);

  const activeTenants =
    useMemo(() => {
      return countActiveTenants(
        tenants
      );
    }, [tenants]);

  const closeMenu = () => {
    setMenuAnchor(null);
    setMenuTenant(null);
  };

  const openMenu = (
    event: MouseEvent<HTMLElement>,
    tenant: TenantRecord
  ) => {
    setMenuAnchor(
      event.currentTarget
    );

    setMenuTenant(tenant);
  };

  const openCreateDialog = () => {
    setEditingTenant(null);
    setFormError(null);
    setFormOpen(true);
  };

  const openEditDialog = (
    tenant: TenantRecord
  ) => {
    closeMenu();
    setEditingTenant(tenant);
    setFormError(null);
    setFormOpen(true);
  };

  const openPasswordDialog = (
    tenant: TenantRecord
  ) => {
    closeMenu();
    setPasswordError(null);
    setPasswordTenant(tenant);
  };

  const openConfirmation = (
    tenant: TenantRecord,
    action: Exclude<
      ConfirmationAction,
      null
    >
  ) => {
    closeMenu();
    setDeleteConfirmation('');
    setConfirmationTenant(tenant);
    setConfirmationAction(action);
  };

  const confirmationTitle =
    confirmationAction === 'delete'
      ? 'Delete tenant permanently'
      : confirmationAction ===
          'suspend'
        ? 'Suspend laboratory tenant'
        : 'Activate laboratory tenant';

  const confirmationBusy =
    statusMutation.isPending ||
    deleteMutation.isPending;

  const deleteConfirmed =
    confirmationAction !== 'delete' ||
    deleteConfirmation ===
      confirmationTenant?.domain;

  return {
    search,
    setSearch,
    formOpen,
    setFormOpen,
    editingTenant,
    setEditingTenant,
    passwordTenant,
    setPasswordTenant,
    confirmationTenant,
    setConfirmationTenant,
    confirmationAction,
    setConfirmationAction,
    deleteConfirmation,
    setDeleteConfirmation,
    formError,
    setFormError,
    passwordError,
    setPasswordError,
    menuAnchor,
    menuTenant,
    tenants,
    isPending,
    isError,
    isFetching,
    refetch,
    createMutation,
    updateMutation,
    statusMutation,
    deleteMutation,
    passwordMutation,
    impersonationMutation,
    filteredTenants,
    totals,
    activeTenants,
    closeMenu,
    openMenu,
    openCreateDialog,
    openEditDialog,
    openPasswordDialog,
    openConfirmation,
    confirmationTitle,
    confirmationBusy,
    deleteConfirmed,
  };
};
