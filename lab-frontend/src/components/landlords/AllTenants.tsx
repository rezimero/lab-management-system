import {
  Box,
} from '@mui/material';

import TenantActionMenu from './TenantActionMenu';
import TenantConfirmationDialog from './TenantConfirmationDialog';
import TenantFormDialog from './TenantFormDialog';
import TenantManagementHero from './TenantManagementHero';

import {
  TenantManagementError,
  TenantManagementLoading,
} from './TenantManagementStates';

import TenantPasswordResetDialog from './TenantPasswordResetDialog';
import TenantPortfolio from './TenantPortfolio';
import TenantSummaryCards from './TenantSummaryCards';

import type {
  ConfirmationAction,
  TenantRecord,
} from './tenantManagementTypes';

import {
  useTenantManagement,
} from './useTenantManagement';

const AllTenants = () => {
  const {
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
  } = useTenantManagement();

  const closeConfirmation = () => {
    setConfirmationTenant(null);
    setConfirmationAction(null);
    setDeleteConfirmation('');
  };

  const confirmTenantAction = (
    tenant: TenantRecord,
    action: Exclude<
      ConfirmationAction,
      null
    >
  ) => {
    if (action === 'delete') {
      deleteMutation.mutate(
        tenant
      );

      return;
    }

    statusMutation.mutate({
      tenant,
      action,
    });
  };

  if (isPending) {
    return (
      <TenantManagementLoading />
    );
  }

  if (isError) {
    return (
      <TenantManagementError
        onRetry={() => {
          void refetch();
        }}
      />
    );
  }

  return (
    <Box
      sx={{
        display: 'grid',
        gap: 3,
      }}
    >
      <TenantManagementHero
        onCreate={openCreateDialog}
      />

      <TenantSummaryCards
        tenantCount={tenants.length}
        activeTenants={activeTenants}
        totals={totals}
      />

      <TenantPortfolio
        search={search}
        tenants={filteredTenants}
        isFetching={isFetching}
        maintenancePending={
          impersonationMutation.isPending
        }
        onSearchChange={setSearch}
        onRefresh={() => {
          void refetch();
        }}
        onMaintain={(tenant) => {
          impersonationMutation.mutate(
            tenant
          );
        }}
        onManage={openMenu}
      />

      <TenantActionMenu
        anchorEl={menuAnchor}
        tenant={menuTenant}
        onClose={closeMenu}
        onEdit={openEditDialog}
        onResetPassword={
          openPasswordDialog
        }
        onConfirm={openConfirmation}
      />

      <TenantFormDialog
        open={formOpen}
        tenant={editingTenant}
        submitting={
          createMutation.isPending ||
          updateMutation.isPending
        }
        serverError={formError}
        onClose={() => {
          setFormOpen(false);
          setEditingTenant(null);
          setFormError(null);
        }}
        onCreate={async (payload) => {
          await createMutation.mutateAsync(
            payload
          );
        }}
        onUpdate={async (
          tenantId,
          payload
        ) => {
          await updateMutation.mutateAsync({
            tenantId,
            payload,
          });
        }}
      />

      <TenantPasswordResetDialog
        open={Boolean(passwordTenant)}
        tenant={passwordTenant}
        submitting={
          passwordMutation.isPending
        }
        serverError={passwordError}
        onClose={() => {
          setPasswordTenant(null);
          setPasswordError(null);
        }}
        onSubmit={async (
          tenantId,
          payload
        ) => {
          await passwordMutation.mutateAsync({
            tenantId,
            payload,
          });
        }}
      />

      <TenantConfirmationDialog
        tenant={confirmationTenant}
        action={confirmationAction}
        title={confirmationTitle}
        busy={confirmationBusy}
        deleteConfirmation={
          deleteConfirmation
        }
        deleteConfirmed={
          deleteConfirmed
        }
        onDeleteConfirmationChange={
          setDeleteConfirmation
        }
        onClose={closeConfirmation}
        onConfirm={confirmTenantAction}
      />
    </Box>
  );
};

export default AllTenants;
